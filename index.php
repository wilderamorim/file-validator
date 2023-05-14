<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

array_map(fn($file) => include_once __DIR__ . '/' . $file, ['helpers.php', 'FileValidator.php',]);

$page = filter_input(INPUT_GET, 'page');
$isMultiple = $page == 'multiple';

$allowedTypes = ['image/jpeg', 'image/png'];
$accept = implode(', ', $allowedTypes);

$validator = new FileValidator();
$validator->setAllowedExtensions(['jpg', 'jpeg', 'png']);
$validator->setAllowedTypes($allowedTypes);
$validator->setMaxSize(1, $validator::UNIT_MB);
$validator->setTotalMaxSize(10, $validator::UNIT_MB); // only if multiple

if (!empty($_FILES['image'])) {

    $files = $_FILES['image'];

    $error = false;
    try {
        $validator->validateFile($files);

        if (!$validator->isMultiple($files)) {
            upload($files, __DIR__ . '/upload');
        } else {
            $multipleFiles = $validator->getMultipleFiles($files);
            foreach ($multipleFiles as $files) {
                upload($files, __DIR__ . '/upload');
            }
        }
    } catch (Exception $exception) {
        $error = $exception->getMessage();
    }

}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <title>File Validator</title>
</head>
<body class="bg-dark">
<div class="container">
    <div class="row align-items-center justify-content-center vh-100">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <?php if (isset($error) && !$error): ?>
                        <div class="alert alert-success" role="alert">
                            Arquivo(s) validados!
                        </div>
                    <?php elseif (isset($error)): ?>
                        <div class="alert alert-warning" role="alert">
                            <?= $error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" enctype="multipart/form-data">
                        <div class="nav nav-tabs">
                            <button onclick="window.location.href='?page=single';" class="nav-link <?= !$isMultiple ? 'active' : null; ?>" type="button">
                                Single
                            </button>
                            <button onclick="window.location.href='?page=multiple';" class="nav-link <?= $isMultiple ? 'active' : null; ?>" type="button">
                                Multiple
                            </button>
                        </div>
                        <?php
                        $attributes = [
                            'accept' => $accept,
                            'onchange' => 'validateFileSize(this);',
                            'data-units' => htmlspecialchars(json_encode($validator->getUnits()), ENT_QUOTES, 'UTF-8'),
                        ];
                        $htmlAttributes = implode(' ', array_map(fn($k, $v) => $k . '="' . $v . '"', array_keys($attributes), $attributes));
                        ?>
                        <?php if ($isMultiple): ?>
                            <div class="form-group">
                                <label for="image">Multiple</label>
                                <input name="image[]" <?= $htmlAttributes; ?> type="file" id="image" class="form-control-file" multiple>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label for="image">Single</label>
                                <input name="image" <?= $htmlAttributes; ?> type="file" id="image" class="form-control-file">
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary btn-block">
                            Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    /**
     * Validates the size of one or multiple files selected in a file input.
     *
     * @param {HTMLInputElement} input - The file input element.
     * @returns {boolean} - True if all files are within the size limit, false otherwise.
     */
    function validateFileSize(input) {
      const units = $(input).data("units") || {};
      const maxSize = units.maxSize || {};
      const totalMaxSize = units.totalMaxSize || {};
      const files = input.files;

      if (maxSize.bytes && maxSize.size && maxSize.unit) {
        for (let i = 0; i < files.length; i++) {
          const file = files[i];
          if (file.size > maxSize.bytes) {
            alert(`The file '${file.name}' exceeds the maximum allowed size of ${maxSize.size}${maxSize.unit}.`);
            input.value = "";
            return false;
          }
        }
      }

      if (input.hasAttribute("multiple") && totalMaxSize.bytes && totalMaxSize.size && totalMaxSize.unit) {
        const totalSizeBytes = Array.from(files).reduce((acc, curr) => acc + curr.size, 0);
        if (totalSizeBytes > totalMaxSize.bytes) {
          alert(`The total size of the files exceeds the maximum allowed size of ${totalMaxSize.size}${totalMaxSize.unit}.`);
          input.value = "";
          return false;
        }
      }

      return true;
    }
</script>
</body>
</html>