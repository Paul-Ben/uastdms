@extends('dashboards.index')
@section('content')
<style>
      @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
</style>
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Send Document</h6>
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div>
                    {{-- <a class="btn btn-sm btn-primary" href="{{ route('document.create') }}">Add Document</a> --}}
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                            class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="container">
                <h1></h1>
                <form action="{{ route('document.senddoc', $document) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="recipient_email">Select Staff to minute to:</label>

                        <select class="form-control selectpicker" name="recipient_id[]" id="recipients" multiple="multiple">
                            <option value="" disabled>Select recipients</option>
                            @foreach ($recipients as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->userDetail->tenant->name ?? 'Citizen User' }}| {{ $user->name }} |
                                    {{ $user->userDetail->designation ?? $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="selected-items" id="selectedItems"></div>
                    </div>
                    <div class="form-group" hidden>
                        <label for="subject">Subject</label>
                        <input type="text" value="{{ $document->id }}" class="form-control" id="subject"
                            name="document_id" required>
                    </div>
                    {{-- <div class="form-group mb-3">
                        <label for="message">Message/Minuting</label>
                        <!-- Suggestion Chips -->
                    <div class="mb-3" id="suggestion-container" style="display: none;">
                        <label class="form-label">Suggestions:</label>
                        <div id="suggestions">
                            <span class="badge bg-primary suggestion" style="cursor:pointer;">Please treat.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please act.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please treat as urgent.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please advise.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please bring up.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">For your necessary action.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please Keep in view.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">This is for your information.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Write a Brief.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Put away.</span>
                        </div>
                    </div>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div> --}}
                      <div class="form-group mb-3">
                        <label for="message">Message/Minuting</label>
                        <!-- Suggestion Chips -->
                        <div class="mb-3" id="suggestion-container" style="display: none;">
                            <label class="form-label">Suggestions:</label>
                            <div id="suggestions">
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please treat.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please act.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please treat as urgent.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please advise.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please bring up.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">For your necessary action.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Please Keep in view.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">This is for your information.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Write a Brief.</span>
                                <span class="badge bg-primary suggestion" style="cursor:pointer;">Put away.</span>
                            </div>
                        </div>
                        <div class="position-relative">
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            <button type="button" id="speechButton" class="btn btn-link position-absolute" style="right: 10px; bottom: 10px;">
                                <i class="fas fa-microphone" id="micIcon" style="font-size: 1.2rem; color: #6c757d;"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-12 col-xl-6 mb-3">
                        <label for="exampleInputEmail1" class="form-label">Attach Document</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf">
                    </div>
                    <button type="submit" class="btn btn-primary mt-4">Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#recipients').selectpicker({
                theme: 'bootstrap4',
                placeholder: "Select recipients",
                allowClear: true,
                tags: false,
            });
        });
    </script>
    <script>
                 // Speech Recognition Setup
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        
        const speechButton = document.getElementById('speechButton');
        const micIcon = document.getElementById('micIcon');
        let isListening = false;

        speechButton.addEventListener('click', () => {
            if (!isListening) {
                // Start listening
                recognition.start();
                isListening = true;
                micIcon.style.color = '#dc3545'; // Red color when active
                micIcon.style.animation = 'pulse 1.5s infinite';
            } else {
                // Stop listening
                recognition.stop();
                isListening = false;
                micIcon.style.color = '#6c757d';
                micIcon.style.animation = 'none';
            }
        });

        recognition.onresult = (event) => {
            const transcript = Array.from(event.results)
                .map(result => result[0])
                .map(result => result.transcript)
                .join('');
            
            document.getElementById('message').value = transcript;
        };

        recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            isListening = false;
            micIcon.style.color = '#6c757d';
            micIcon.style.animation = 'none';
        };


        const textarea = document.getElementById('message');
        const suggestionContainer = document.getElementById('suggestion-container');
        const suggestions = document.querySelectorAll('.suggestion');
    
        // Show suggestions on focus
        textarea.addEventListener('focus', () => {
            suggestionContainer.style.display = 'block';
        });
    
        // Hide suggestions when focus is lost (optional)
        textarea.addEventListener('blur', () => {
            setTimeout(() => {
                suggestionContainer.style.display = 'none';
            }, 200); // delay to allow chip click before hiding
        });
    
        // Handle suggestion click
        suggestions.forEach(suggestion => {
            suggestion.addEventListener('click', () => {
                textarea.value = textarea.value.trim()
                    ? textarea.value + ' ' + suggestion.textContent
                    : suggestion.textContent;
                textarea.focus();
            });
        });
    </script>
@endsection
