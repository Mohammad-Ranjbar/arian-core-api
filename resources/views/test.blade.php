<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<input type="file" id="file">
<script src="https://cdn.jsdelivr.net/npm/tus-js-client@latest/dist/tus.min.js"></script>
<script>
    document.getElementById('file').addEventListener('change', function (e) {
        // Get the selected file from the input element
        var file = e.target.files[0]

        // Create a new tus upload
        var upload = new tus.Upload(file, {
            endpoint: 'http://localhost:8000/api/tus/',
            retryDelays: [0, 3000, 5000, 10000, 20000],
            chunkSize: 1024 * 512,
            metadata: {
                filename: Math.random()+file.name,
                filetype: file.type,
            },
            onError: function (error) {
                console.log('Failed because: ' + error)
            },
            onProgress: function (bytesUploaded, bytesTotal) {
                var percentage = ((bytesUploaded / bytesTotal) * 100).toFixed(2)
                console.log(bytesUploaded, bytesTotal, percentage + '%')
            },
            onSuccess: function () {
                console.log('Download %s from %s', upload.file.name, upload.url)
            },
        })

        // Check if there are any previous uploads to continue.
        upload.findPreviousUploads().then(function (previousUploads) {
            // Found previous uploads so we select the first one.
            if (previousUploads.length) {
                upload.resumeFromPreviousUpload(previousUploads[0])
            }

            // Start the upload
            upload.start()
        })
    })
</script>
</body>
</html>