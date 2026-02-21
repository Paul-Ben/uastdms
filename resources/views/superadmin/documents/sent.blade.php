{{-- @extends('dashboards.index')
@section('content')
    <!-- Button Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
               
            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Sent Documents</h6>
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('document.create') }}">Add Document</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('dashboard') }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="table-responsive">
                <table id="superDocSent" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Document No</th>
                            <th scope="col">Title</th>
                            <th scope="col">Sent To</th>
                            <th scope="col">Comment</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sent_documents as $key => $sent)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{$sent->document->docuent_number}}</td>
                                <td>{{$sent->document->title}}</td>
                                <td>
                                    {{$recipient[0]->name}}
                                    <span>{{$recipient[0]->email}}</span>
                                </td>
                                <td>{{$sent->message}}</td>
                                <td>
                                    <div class="nav-item dropdown">
                                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Details</a>
                                        <div class="dropdown-menu">
                                            <a href="edit_studet.html" class="dropdown-item">Edit</a>
                                            <a href="delete_student.html" class="dropdown-item" style="background-color: rgb(239, 79, 79)">Delete</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="text-center">
                                <td></td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
           
        </div>
    </div>
    <!-- Table End -->
    <script>
        $(document).ready(function() {
            $('#superDocSent').DataTable({
                responsive: true,
                autoWidth: false,
                paging: true, // Enable pagination
                searching: true, // Enable search
                ordering: true, // Enable sorting
                lengthMenu: [10, 25, 50, 100], // Dropdown for showing entries
                columnDefs: [{
                        orderable: false,
                        targets: -1
                    } // Disable sorting on last column (Actions)
                ],
                language: {
                    searchPlaceholder: "Search here...",
                    zeroRecords: "No matching records found",
                    lengthMenu: "Show entries",
                    // info: "Showing START to END of TOTAL entries",
                    infoFiltered: "(filtered from MAX total entries)",
                }
            });
        });
    </script>
@endsection --}}

@extends('dashboards.index')
@section('content')
    <!-- Button Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">

            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Outgoing Mails</h6>
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('document.create') }}">Add Document</a>
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() }}"><i
                            class="fa fa-arrow-left me-2"></i>Back</a>
                </div>

            </div>
            <div class="table-responsive">
                <table id="visitLogsTable" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Document No</th>
                            <th scope="col">Subject</th>
                            <th scope="col">Sent To</th>
                            {{-- <th scope="col" style="width: 16.66%;">Comment</th> --}}
                            <th scope="col">Date</th>
                            {{-- <th scope="col">Actions</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sent_documents as $key => $sent)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><a href="{{ route('document.view', $sent) }}">
                                        {{ $sent->document->docuent_number }}
                                    </a>
                                </td>
                                <td>{{ $sent->document->title }}</td>
                                <td>
                                    {{ $sent->recipient_details[0]->userDetail->designation }}, <br>
                                    @if ($sent->recipient_details[0]->userDetail->tenant->name)
                                        <span>{{ $sent->recipient_details[0]->userDetail->tenant->name }}</span>
                                    @endif
                                </td>
                                <td>{{ $sent->updated_at->format('M j, Y g:i A') }}</td>
                                {{-- <td>
                                    
                                    <a href="{{ route('folders.select', $sent->document->id) }}" 
                                        class="btn btn-sm btn-primary  p-2"
                                        title="Add to folder">
                                         <i class="fas fa-folder-plus"></i>
                                     </a>
                                  
                                </td> --}}
                            </tr>
                        @empty
                            <tr class="text-center">
                                <td></td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                                <td>No Data Found</td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
            {{-- @if ($sent_documents->count() > 0)
            <div class="mt-3">
                {{$sent_documents->links('pagination::bootstrap-5')}}
            </div>
            @endif --}}
        </div>
    </div>
    <!-- Table End -->
@endsection
