$body = @{
    nama = "Test User"
    email = "testuser@example.com"
    password = "password123"
    role = "siswa"
    kelas = "X MIPA 1"
} | ConvertTo-Json

Write-Host "Testing Registration API..."
Write-Host "Request Body: $body"

try {
    $response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/register" -Method POST -ContentType "application/json" -Body $body -ErrorAction Stop
    Write-Host "`nRegistration Success!"
    $response | ConvertTo-Json -Depth 10
} catch {
    Write-Host "`nRegistration Failed!"
    Write-Host "Error: $($_.Exception.Message)"
    Write-Host "Status Code: $($_.Exception.Response.StatusCode.value__)"
    $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
    $reader.BaseStream.Position = 0
    $reader.DiscardBufferedData()
    $responseBody = $reader.ReadToEnd()
    Write-Host "Response Body: $responseBody"
}
