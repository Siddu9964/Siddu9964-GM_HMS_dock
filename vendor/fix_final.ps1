$files = @(
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\login.php',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\index.php',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\assets\js\vendor.js',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\assets\css\vendor.css'
)
foreach ($file in $files) {
    $c = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)
    $c = $c.Replace('#0891b2', '#06b6d4')
    $c = $c.Replace('8,145,178', '6,182,212')
    [System.IO.File]::WriteAllText($file, $c, [System.Text.Encoding]::UTF8)
    Write-Host "Fixed: $file"
}
Write-Host "All done."
