<?php
include 'ini.php';

if ($_FILES['csv_file']['error'] == 0) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (mime_content_type($file) !== "text/plain") {
        die("يرجى رفع ملف CSV صالح.");
    }

    $handle = fopen($file, "r");
    $data = [];
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $data[] = $row;
    }
    fclose($handle);
} else {
    die("حدث خطأ أثناء رفع الملف.");
}

include 'inc/header.php';
?>

<div class="container">
    <h3 class="text-center">معاينة البيانات</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <?php foreach ($data[0] as $header) : ?>
                    <th><?php echo htmlspecialchars($header); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i < count($data); $i++) : ?>
                <tr>
                    <?php foreach ($data[$i] as $cell) : ?>
                        <td><?php echo htmlspecialchars($cell); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    <form action="generate_pdf.php" method="POST">
        <input type="hidden" name="csv_data" value="<?php echo base64_encode(serialize($data)); ?>">
        <button type="submit" class="btn btn-success">تحميل PDF</button>
    </form>
</div>

<?php include 'inc/footer.php'; ?>
<?php
include 'ini.php';

if ($_FILES['csv_file']['error'] == 0) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (mime_content_type($file) !== "text/plain") {
        die("يرجى رفع ملف CSV صالح.");
    }

    $handle = fopen($file, "r");
    $data = [];
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $data[] = $row;
    }
    fclose($handle);
} else {
    die("حدث خطأ أثناء رفع الملف.");
}

include 'inc/header.php';
?>

<div class="container">
    <h3 class="text-center">معاينة البيانات</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <?php foreach ($data[0] as $header) : ?>
                    <th><?php echo htmlspecialchars($header); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i < count($data); $i++) : ?>
                <tr>
                    <?php foreach ($data[$i] as $cell) : ?>
                        <td><?php echo htmlspecialchars($cell); ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    <form action="generate_pdf.php" method="POST">
        <input type="hidden" name="csv_data" value="<?php echo base64_encode(serialize($data)); ?>">
        <button type="submit" class="btn btn-success">تحميل PDF</button>
    </form>
</div>

<?php include 'inc/footer.php'; ?>
