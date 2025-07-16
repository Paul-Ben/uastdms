@extends('dashboards.index')
@section('content')
    <!-- Button Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
               @if (session('success'))
               <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>{{session('success')}}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
               @endif
               <script>
                @if(session()->has('toastr'))
                    {!! session('toastr') !!}
                @endif
            </script>
            </div>
        </div>
    </div>
    <!-- Button End -->

    <!-- Table Start -->
    <div class="container-fluid pt-4 px-4">
        <div class="bg-light text-center rounded p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="mb-0">Document Management</h6>
                {{-- <div>
                    <form method="GET" action="{{route('search.doc')}}">
                        <div class="input-group mb-3">
                            <input type="text" name="search" class="form-control" placeholder="Search items..."
                                value="">
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </form>
                </div> --}}
                <div>
                    <a class="btn btn-sm btn-primary" href="{{ route('document.file') }}">File New Document</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('dashboard') }}"><i class="fa fa-arrow-left me-2"></i>Back</a>
                </div>
            </div>
            <div class="table-responsive">
                <table id="userDocs" class="table text-start align-middle table-bordered table-hover mb-0">
                    <thead>
                        <tr class="text-dark">
                            <th scope="col">#</th>
                            <th scope="col">Document No</th>
                            <th scope="col">Title</th>
                            {{-- <th scope="col">Uploaded By</th> --}}
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $key => $document)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><a target="_blank" href="{{asset('storage/'. $document->file_path)}}">{{$document->docuent_number}}</a></td>
                                <td>{{$document->title}}</td>
                                {{-- <td></td> --}}
                                <td>Processing</td>
                                <td>
                                    <div class="nav-item">
                                        <a target="_blank" href="{{asset('storage/'. $document->file_path)}}" class="nav-link">View</a>
                        
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr class="text-center">
                                <td >No Data Found</td>
                                <td >No Data Found</td>
                                <td >No Data Found</td>
                                <td >No Data Found</td>
                                <td >No Data Found</td>
                                </tr>
                        @endforelse

                    </tbody>
                </table>
                <div class="pt-4">
                    
                </div>
            </div>
        </div>
    </div>
    <!-- Table End -->

    <script>
        $(document).ready(function() {
            $('#userDocs').DataTable({
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
@endsection
