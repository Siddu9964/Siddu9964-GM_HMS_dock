$files = @(
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\login.php',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\index.php',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\assets\js\vendor.js',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\assets\css\vendor.css'
)
$from = @('2563eb','3b82f6','37,99,235','1e3a8a','0ea5e9')
$to   = @('0891b2','06b6d4','8,145,178','0e5f75','22d3ee')

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)
    for ($i = 0; $i -lt $from.Length; $i++) {
        $content = $content.Replace($from[$i], $to[$i])
    }
    [System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
    Write-Host "Fixed: $file"
}
Write-Host "All done."
