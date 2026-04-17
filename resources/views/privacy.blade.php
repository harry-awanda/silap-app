<!DOCTYPE html>

<html lang="id" data-assets-path="{{ asset('assets') }}" data-template="vertical-menu-template">

  <head>
    <meta charset="utf-8" />
    <meta name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title> Privacy | SILAP </title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="#" />

    <!-- Fonts -->
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;display=swap"
      rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css')}}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css')}}" class="template-customizer-core-css" />

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css')}}"
      class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css')}}" />
    @stack('styles')

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}" />

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js')}}"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <script src="{{ asset('assets/vendor/js/template-customizer.js')}}"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('assets/js/config.js')}}"></script>

  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <!-- Layout container -->
      <div class="layout-container">
        
        <!-- Layout page -->
        <div class="layout-page">
          
          <!-- Content wrapper -->
          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">
              <h4 class="py-3 mb-4">
                <span class="text-muted fw-light">SILAP /</span> Kebijakan Privasi
              </h4>

              <div class="card mb-4">
                <div class="card-body">
                  <h5 class="card-title">Kebijakan Privasi</h5>
                  <p class="text-muted">Terakhir diperbarui: 26 September 2025</p>

                  <p>
                    Kebijakan Privasi ini menjelaskan bagaimana kami mengelola data pribadi Anda saat menggunakan aplikasi
                    <strong>SILAP</strong>. Dengan menggunakan aplikasi ini, Anda dianggap menyetujui praktik yang dijelaskan.
                  </p>

                  <div class="accordion" id="privacyAccordion">
                    {{-- Data yang Kami Kumpulkan --}}
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingData">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseData" aria-expanded="true" aria-controls="collapseData">
                          Data yang Kami Kumpulkan
                        </button>
                      </h2>
                      <div id="collapseData" class="accordion-collapse collapse show" aria-labelledby="headingData" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                          <ul>
                            <li>Data identitas: nama, NIS/NIP, kelas, peran pengguna.</li>
                            <li>Data presensi: waktu, status hadir, lokasi GPS, foto (jika diaktifkan).</li>
                            <li>Data perangkat & teknis: alamat IP, user agent, log aktivitas.</li>
                            <li>Cookies sesi & preferensi tampilan.</li>
                          </ul>
                        </div>
                      </div>
                    </div>

                    {{-- Cara Kami Menggunakan Data --}}
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingUse">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUse" aria-expanded="false" aria-controls="collapseUse">
                          Cara Kami Menggunakan Data
                        </button>
                      </h2>
                      <div id="collapseUse" class="accordion-collapse collapse" aria-labelledby="headingUse" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                          <ul>
                            <li>Mencatat & memverifikasi kehadiran berbasis lokasi/foto.</li>
                            <li>Menyusun laporan untuk wali kelas & kesiswaan.</li>
                            <li>Mendeteksi anomali & mencegah penyalahgunaan akun.</li>
                            <li>Dukungan teknis & peningkatan layanan.</li>
                          </ul>
                        </div>
                      </div>
                    </div>

                    {{-- Berbagi Data --}}
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingShare">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseShare" aria-expanded="false" aria-controls="collapseShare">
                          Berbagi Data
                        </button>
                      </h2>
                      <div id="collapseShare" class="accordion-collapse collapse" aria-labelledby="headingShare" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                          <p>Data hanya dibagikan dengan:</p>
                          <ul>
                            <li>Pihak internal sekolah sesuai kewenangan.</li>
                            <li>Penyedia layanan pendukung (hosting/server).</li>
                            <li>Penegak hukum bila diwajibkan.</li>
                          </ul>
                          <p><strong>Kami tidak menjual data pribadi Anda.</strong></p>
                        </div>
                      </div>
                    </div>

                    {{-- Hak Anda --}}
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingRights">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRights" aria-expanded="false" aria-controls="collapseRights">
                          Hak Anda
                        </button>
                      </h2>
                      <div id="collapseRights" class="accordion-collapse collapse" aria-labelledby="headingRights" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                          <ul>
                            <li>Mengakses & memperbaiki data pribadi.</li>
                            <li>Meminta penghapusan data tertentu.</li>
                            <li>Menarik persetujuan fitur tertentu (lokasi/kamera).</li>
                          </ul>
                        </div>
                      </div>
                    </div>

                    {{-- Privasi Anak --}}
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingChild">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChild" aria-expanded="false" aria-controls="collapseChild">
                          Privasi Anak
                        </button>
                      </h2>
                      <div id="collapseChild" class="accordion-collapse collapse" aria-labelledby="headingChild" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                          <p>
                            SILAP digunakan di lingkungan pendidikan formal dengan pengawasan sekolah.
                            Untuk pengguna di bawah 13 tahun, data diproses untuk tujuan pendidikan dengan persetujuan & pengawasan sekolah/wali.
                          </p>
                        </div>
                      </div>
                    </div>

                    {{-- Kontak --}}
                    <div class="accordion-item">
                      <h2 class="accordion-header" id="headingContact">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContact" aria-expanded="false" aria-controls="collapseContact">
                          Kontak
                        </button>
                      </h2>
                      <div id="collapseContact" class="accordion-collapse collapse" aria-labelledby="headingContact" data-bs-parent="#privacyAccordion">
                        <div class="accordion-body">
                          <ul>
                            <li><strong>Sekolah:</strong> SMK Negeri 4 Tanjungpinang</li>
                            <li><strong>Email:</strong> admin@smkn4tpi.com</li>
                            <li><strong>Alamat:</strong> Jl. ……, Tanjungpinang, Kep. Riau</li>
                            <li><strong>Telepon:</strong> (0xx) xxxx-xxxx</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div> {{-- End Accordion --}}
                </div>
              </div>
            </div>
            @include('layouts.footer')
            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>
      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js')}}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js')}}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js')}}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')}}"></script>
    <script src="{{ asset('assets/vendor/libs/hammer/hammer.js')}}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js')}}"></script>

    <!-- Vendors JS -->
    <script src="{{asset('assets/vendor/libs/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
    <script src="{{ asset('assets/vendor/libs/select2/select2.js' )}}"></script>
    @stack('scripts')

    <!-- Main JS -->
    <script src="{{ asset('assets/js/main.js')}}"></script>
    <script src="{{ asset('assets/js/init-select2.js')}}"></script>

  </body>

</html>