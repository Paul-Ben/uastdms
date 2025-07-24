<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UAST Electronic Document Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('landing/css/style.css') }}">
    <link rel="icon" href="{{ asset('landing/images/logo.jpeg') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('landing/images/logo.jpeg') }}" type="image/x-icon" />

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@100..800&display=swap" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Sora:wght@100..800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet">
    <style>
        .iti {
            width: 100%;
            /* Ensure it takes full width */
            height: 100%;

        }

        .iti__flag-container {
            margin-right: 10px;
            /* Adjust spacing */
        }

        .iti__selected-flag {
            padding: 0 20px;
            /* Adjust padding */
        }

        .iti__arrow {
            border-top-color: #000;
            /* Match arrow color */
        }

        .iti__country-list {
            border-radius: 4px;
            /* Match border radius */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            /* Add shadow */
        }
    </style>

    <!---- ReCaptcha ------>
    {!! NoCaptcha::renderJs() !!}

</head>

<body>

    <!-- Navigation Start -->
    <nav class="navbar navbar-expand-lg  lg bg-body-tertiary ">
        <div class="container ">
            <a class="navbar-brand" href="#"><img src="{{ asset('landing/images/uastLogo.jpeg') }}"
                    style="border-radius: 1em" alt="e-filling-logo" height="50"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav mb-2 mb-lg-0  ">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{ url('/') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/#abt">About</a>
                    </li>

                    <li class="nav-item @if(request()->is('/contact')) active @endif">
                        <a class="nav-link @if(Route::is('contact')) active @endif" aria-current="page" href="{{route('contact')}}">Contact</a>
                    </li>
                </ul>

                <div class="ms-auto">
                    <ul class="navbar-nav ">
                        <div class="">
                            @if (Route::is('login'))
                                <a href="{{ route('register') }}">
                                    <button type="button" class="btn btn-success ">Register</button>
                                </a>
                            @else
                                <a href="{{ route('login') }}">
                                    <button type="button" class="btn btn-success ">Login</button>
                                </a>
                            @endif
                        </div>
                        <!-- <li class="nav-item "> <a class="nav-link "  href="#">Login</a> </li> -->
                    </ul>
                </div>

            </div>
        </div>
    </nav>

    <!-- Navigation End -->
    @yield('content')
     <!-- Footer start -->
     <div class="row  text-white text-center">
        <div class="footer-container">
            <div class="row mt-4">
                <!-- First row, three columns -->

                <div class="col-md-4 p-5 text-center">

                    <!-- <div class="d-flex align-items-center text-center" style="margin-left:40%">
                        <img src="images/SEAL BENUE STATE GOV 1.svg" style="padding-right: 5px;">
                        <p style="text-align: left;">Benue State Government
                            Integrated Document Management System</p>
                    </div> -->
                    <div class="d-flex text-start">
                        <a href="#" class="text-light me-2"><img
                                src="{{ asset('landing/images/uastLogo.jpeg') }}" width="65"></a> University
                        of Agriculture, Science and Technology Ihuh
                        Electronic Document Management System
                    </div>
                </div>



                <div class="col-md-4 p-4">


                    <h5>Quick Links</h5>
                    <a href="{{ url('/') }}" class="text-light me-3">Home</a>
                    <a href="#abt" class="text-light me-3">About us</a>
                    <a href="{{route('contact')}}" class="text-light me-3">Contact us</a>
                    <a href="{{ route('login') }}" class="text-light">Login</a>

                    <!-- <div class="row">
                        <div class="col-md-3">
                            <a href="#" class="text-white">Home</a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" class="text-white">About us</a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" class="text-white">Contact us</a>
                        </div>
                        <div class="col-md-3">
                            <a href="#" class="text-white">Login</a>
                        </div>
                    </div> -->

                </div>
                <div class="col-md-4 p-4">
                    <!-- <div class="row text-center" style="text-align: center; padding: 2%;">
                        <div class="col-md-3">
                            <img src="images/1.svg" >
                        </div>
                        <div class="col-md-3">
                            <img src="images/2.svg" >
                        </div>
                        <div class="col-md-3">
                            <img src="images/3.svg" >
                        </div>
                        <div class="col-md-3">
                            <img src="images/4.svg" >
                        </div>
                    </div> -->

                    <h5>Follow Us</h5>
                    <a href="#" class="text-light me-3"><img src="{{ asset('landing/images/1.svg') }}"></a>
                    <a href="#" class="text-light me-3"><img src="{{ asset('landing/images/2.svg') }}"></a>
                    <a href="#" class="text-light me-3"><img src="{{ asset('landing/images/3.svg') }}"></a>
                    <a href="#" class="text-light"><img src="{{ asset('landing/images/4.svg') }}"></a><br><br>
                    <span class="body-text">
                        <a href="#" class="text-light me-3" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal">Privacy Policy</a>
                        <a href="#" class="text-light me-3">Terms & Conditions</a>
                    </span>
                </div>
            </div>

            <hr>

            <div class="row mt-4 p-4">

                <div class="col-md-4">
                    &nbsp;
                </div>
                <div class="col-md-4">
                    <p>© Copyright 2025, All Rights Reserved</p>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center text-center" style="margin-left:40%">
                        <p style="padding-right: 5px;">Powered by BDIC</p><a href="https://bdic.ng/" target="__blank"><img
                            src="{{ asset('landing/images/BDIC logo 1 1.svg') }}"></a>

                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Footer end -->

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyPolicyModal" tabindex="-1" aria-labelledby="privacyPolicyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyPolicyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Effective Date:</strong> 2025<br>
                    <strong>Last Updated:</strong> 2025</p>
                    
                    <h6>1. Introduction</h6>
                    <p>Welcome to University of Agriculture, Science and Technology Ihuh's Document Management System ("DMS", "we", "us", "our"). This Privacy Policy outlines how we collect, use, disclose, and protect your personal data when you use our services.</p>
                    <p>We are committed to protecting your privacy and ensuring that your personal data is handled in accordance with:</p>
                    <ul>
                        <li>The Nigeria Data Protection Act (NDPA) 2023</li>
                        <li>The European Union General Data Protection Regulation (GDPR)</li>
                    </ul>
                    <p>By accessing or using our DMS, you acknowledge and agree to the practices described in this policy.</p>
                    
                    <h6>2. Data Controller Information</h6>
                    <p>University of Agriculture, Science and Technology Ihuh is the Data Controller responsible for your personal data.</p>
                    <p><strong>Contact Details:</strong><br>
                    Email: info@uast.edu.ng<br>
                    Phone: +234 08169192646<br>
                    Address: Takeoff Campus, Ihugh-Korinya Road, Ihugh Vandeikya LGA, Benue State</p>
                    <p>We may also act as a Data Processor on behalf of your organization where applicable.</p>
                    
                    <h6>3. What Data We Collect</h6>
                    <p>We may collect the following categories of personal data:</p>
                    <p><strong>a. User Information</strong></p>
                    <ul>
                        <li>Name</li>
                        <li>Email address</li>
                        <li>Contact number</li>
                        <li>Address</li>
                        <li>National Identity Number</li>
                        <li>Organization affiliation</li>
                        <li>Job role</li>
                    </ul>
                    <p><strong>b. User Authentication and Login</strong></p>
                    <ul>
                        <li>Username and encrypted passwords</li>
                        <li>Job role and organizational affiliation</li>
                    </ul>
                    <p><strong>b. Document and Content Data</strong></p>
                    <ul>
                        <li>Uploaded files and document contents</li>
                        <li>Document metadata (e.g., titles, tags, authorship, timestamps)</li>
                    </ul>
                    <p><strong>c. Technical and Usage Data</strong></p>
                    <ul>
                        <li>IP address</li>
                        <li>Browser type and device information</li>
                        <li>Access logs and session history</li>
                        <li>Activity logs and audit trails</li>
                    </ul>
                    <p>We do not intentionally collect sensitive personal data (such as health data or biometric data) unless explicitly required and with your informed consent.</p>
                    
                    <h6>4. Legal Basis for Processing</h6>
                    <p>Under the NDPA and GDPR, we process your personal data based on one or more of the following legal bases:</p>
                    <ul>
                        <li><strong>Consent:</strong> Where you have explicitly given permission</li>
                        <li><strong>Contractual necessity:</strong> To fulfill our obligations to you or your organization</li>
                        <li><strong>Legal obligation:</strong> To comply with applicable laws</li>
                        <li><strong>Legitimate interest:</strong> To improve our services, ensure security, and prevent fraud</li>
                    </ul>
                    
                    <h6>5. How We Use Your Data</h6>
                    <p>Your personal data is used for the following purposes:</p>
                    <ul>
                        <li>User authentication and account management</li>
                        <li>Secure storage and sharing of documents</li>
                        <li>Logging actions for compliance and audit purposes</li>
                        <li>System monitoring, performance optimization, and support</li>
                        <li>Regulatory and legal compliance</li>
                    </ul>
                    
                    <h6>6. Data Sharing and Disclosure</h6>
                    <p>We do not sell or rent your personal data.</p>
                    <p>We may disclose your data to:</p>
                    <ul>
                        <li>Authorized personnel within your organization</li>
                        <li>Third-party service providers (e.g., secure cloud hosting, analytics) who operate under data processing agreements</li>
                        <li>Regulators or law enforcement if required by law or court order</li>
                    </ul>
                    
                    <h6>7. International Data Transfers</h6>
                    <p>Where data is transferred across borders (e.g., outside Nigeria or the EU), we ensure such transfers are done in compliance with:</p>
                    <ul>
                        <li>NDPA 2023 cross-border transfer requirements</li>
                        <li>GDPR Standard Contractual Clauses (SCCs) or adequacy decisions for EU-based users</li>
                    </ul>
                    <p>We ensure appropriate safeguards are in place to protect your data during international transfers.</p>
                    
                    <h6>8. Your Rights</h6>
                    <p>As a data subject under NDPA and GDPR, you have the following rights:</p>
                    <ul>
                        <li><strong>Right of access</strong> – to know what personal data we hold about you</li>
                        <li><strong>Right to rectification</strong> – to correct inaccurate or incomplete data</li>
                        <li><strong>Right to erasure</strong> – to request deletion of your data ("right to be forgotten")</li>
                        <li><strong>Right to restrict processing</strong> – to limit how your data is used</li>
                        <li><strong>Right to object</strong> – to object to certain uses (e.g., direct marketing)</li>
                        <li><strong>Right to data portability</strong> – to obtain and reuse your data elsewhere</li>
                        <li><strong>Right to withdraw consent</strong> – at any time, where processing is based on consent</li>
                        <li><strong>Right to lodge a complaint</strong> – with your data protection authority</li>
                    </ul>
                    <p>To exercise any of these rights, contact us at [Insert Contact Email].</p>
                    
                    <h6>9. Data Retention</h6>
                    <p>We retain your personal data only for as long as necessary to:</p>
                    <ul>
                        <li>Fulfill the purposes outlined in this policy</li>
                        <li>Comply with legal, regulatory, or contractual obligations</li>
                        <li>Resolve disputes and enforce agreements</li>
                    </ul>
                    <p>After the retention period, we will securely delete or anonymize your data.</p>
                    
                    <h6>10. Data Security</h6>
                    <p>We implement appropriate technical and organizational measures, including:</p>
                    <ul>
                        <li>End-to-end encryption</li>
                        <li>Role-based access controls</li>
                        <li>Multi-factor authentication</li>
                        <li>Secure server infrastructure</li>
                        <li>Regular security audits and penetration testing</li>
                    </ul>
                    <p>While we take reasonable precautions, no system is completely immune to risk. Users should practice safe data handling and access practices.</p>
                    
                    <h6>11. Cookies and Tracking Technologies</h6>
                    <p>Our DMS uses cookies and similar technologies to:</p>
                    <ul>
                        <li>Maintain user sessions</li>
                        <li>Track usage for performance and analytics</li>
                        <li>Store user preferences</li>
                    </ul>
                    <p>You may manage or disable cookies via your browser settings. Disabling cookies may affect platform functionality.</p>
                    
                    <h6>12. Third-Party Links</h6>
                    <p>Our services may include links to external websites or services. We are not responsible for their content or privacy practices. Please review their privacy policies independently.</p>
                    
                    <h6>13. Children's Privacy</h6>
                    <p>Our platform is not intended for children under the age of 13 (or 16 in the EU). We do not knowingly collect personal data from children. If you believe a child has provided us with data, please contact us to delete it.</p>
                    
                    <h6>14. Changes to This Policy</h6>
                    <p>We may update this Privacy Policy periodically. When we do, we will revise the "Last Updated" date and notify users of material changes via the platform or email.</p>
                    
                    <h6>15. Contact Us</h6>
                    <p>If you have any questions, requests, or complaints about this policy or our handling of your personal data, contact us:</p>
                    <p>University of Agriculture, Science and Technology Ihuh<br>
                    Takeoff Campus, Ihugh-Korinya Road, Ihugh Vandeikya LGA, Benue State, Nigeria<br>
                    Email: info@uast.edu.ng<br>
                    Phone: +234 08169192646</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        @if (Session::has('message'))
            var type = "{{ Session::get('alert-type', 'info') }}"
            switch (type) {
                case 'info':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.info("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();
                    break;
                case 'success':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.success("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();

                    break;
                case 'warning':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.warning("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();

                    break;
                case 'error':

                    toastr.options.timeOut = 10000;
                    toastr.options.closeButton = true;
                    toastr.options.progressBar = true;
                    toastr.error("{{ Session::get('message') }}");
                    var audio = new Audio('audio.mp3');
                    audio.play();

                    break;
            }
        @endif
    </script>

</body>

</html>
