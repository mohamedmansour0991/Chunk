<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<style>
    #uploadStatus {
        width: 100%;
        height: 20px;
        background: #f1f1f1;
        margin: 9px 0;
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        display: none;
    }

    #uploadStatus #progress {
        width: 0%;
        height: 100%;
        background: #3eba54;
    }

    .custom-file-upload {
        border: 1px solid #ccc;
        display: inline-block;
        padding: 6px 12px;
        cursor: pointer;
    }
</style>

<body>
    <div class="container card p-2">
        <form action="{{ route('store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="section-selector mt-5">Lec For:</label>
                <select class="form-control" id="grade" name="grade">
                    <option value="1">1 Sec.</option>
                    <option value="2">2 Sec.</option>
                    <option value="3">3 Sec.</option>
                </select>
            </div>

            <div class="form-group mt-4">
                <label for="section-selector">Week Selector:</label>
                <select class="form-control" id="week" name="week">
                    @for ($week = 1; $week <= 45; $week++)
                        <option value="week{{ $week }}">Week {{ $week }}</option>
                    @endfor
                </select>
                </select>
            </div>

            <div class="form-group mt-4">
                <label for="section-selector">Section Selector:</label>
                <select class="form-control" id="sec" name="sec">
                    @for ($week = 1; $week <= 2; $week++)
                        <option value="sec{{ $week }}">Section {{ $week }}</option>
                    @endfor
                </select>
            </div>

            <div class="row m-0">
                <input type="file" class="form-control col-10" id="video"
                    data-url-upload="{{ route('post-create-video') }}" accept="video/*">
                <input type="text" hidden id="video_path" name="video_path" accept="video/*">
                <button type="button" class="btn btn-secondary col-2" id="uploadVideo">
                    upload
                </button>
            </div>


            <div id="uploadStatus">
                <span id="progress">
                </span>
            </div>
            <div id="uploadCompleteMessage" style="display: none; text-align:center;">Upload complete</div>

            @php
                ini_set('max_execution_time', 0);
                set_time_limit(360);
                ini_set('max_execution_time', 600);
                ini_set('memory_limit', '2048M');
                ini_set('upload_max_filesize', '500M');
                ini_set('post_max_size', '500M');
            @endphp

        </form>
    </div>
</body>

<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.getElementById('uploadVideo').addEventListener('click', function() {
        // Clear the previous "Upload complete" message
        document.getElementById('uploadCompleteMessage').style.display = 'none';

        const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB
        const fileInput = document.getElementById('video');
        const file = fileInput.files[0];
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        let uploadedChunks = 0; // Number of successfully uploaded chunks

        const path = fileInput.getAttribute('data-url-upload');

        // Get the selected values from Week Selector and Section Selector
        const week = $('#week').val();
        const sec = $('#sec').val();

        // Combine Week and Section values to create the title
        const video_title = `Week ${week}, Section ${sec}`;

        if (week.length === 0 || sec.length === 0) {
            // Handle the case where week or sec is empty here
            console.log('Week or Section is empty');
            return;
        } else {
            // Continue with the upload
            function uploadChunk() {
                const start = uploadedChunks * CHUNK_SIZE;
                const end = Math.min(start + CHUNK_SIZE, file.size);
                const blob = file.slice(start, end);
                const formData = new FormData();
                formData.append('video_title', video_title); // Use the combined title
                formData.append('video', blob);
                formData.append('chunkIndex', uploadedChunks);
                formData.append('totalChunks', totalChunks);
                formData.append('grade', $('#grade').val());
                formData.append('week', week);
                formData.append('sec', sec);

                axios.post(path, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: function(progressEvent) {
                        const percentCompleted = Math.round((uploadedChunks / totalChunks) * 100);
                        document.getElementById('uploadStatus').style.display = 'flex';
                        document.getElementById('progress').style.width = percentCompleted + '%';
                        if (percentCompleted === 100) {
                            document.getElementById('uploadCompleteMessage').style.display =
                                'block';
                        }
                        console.log(percentCompleted);
                    }

                }).then(function(response) {
                    if (response.data.status === 'success') {
                        if (uploadedChunks < totalChunks) {
                            uploadChunk();
                        } else {
                            document.getElementById('video_path').value = response.data.path;
                            $('#upload_success').val(response.data.status)
                            $('#upload_success').removeClass('d-none')
                            console.log('Upload complete');
                        }
                    } else {
                        console.log('Upload failed:', response);
                    }
                    uploadedChunks++;

                }).catch(function(error) {
                    console.log('Upload error:', error);
                });
            }
            uploadChunk();
        }
    });
</script>


</html>
