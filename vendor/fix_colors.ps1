$files = @(
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\login.php',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\index.php',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\assets\js\vendor.js',
    'D:\xampp\htdocs\GM_HMS\vendor\vendor_view\assets\css\vendor.css'
)
$from = @('4f46e5','6366f1','1e1b4b','312e81','79,70,229','06b6d4')
$to   = @('2563eb','3b82f6','0f172a','1e3a8a','37,99,235','0ea5e9')

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)
    for ($i = 0; $i -lt $from.Length; $i++) {
        $content = $content.Replace($from[$i], $to[$i])
    }
    [System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
    Write-Host "Fixed: $file"
}
Write-Host "All done."
