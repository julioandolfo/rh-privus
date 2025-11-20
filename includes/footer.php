                    </div>
                    <!--end::Container-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::Root-->
    
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </div>
    <!--end::Scrolltop-->
    
    <!--begin::Javascript-->
    <script>var hostUrl = "../assets/";</script>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="../assets/plugins/global/plugins.bundle.js"></script>
    <script src="../assets/js/scripts.bundle.js"></script>
    <!--end::Global Javascript Bundle-->
    
    <!--begin::Custom Javascript-->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!--end::Custom Javascript-->
    
    <!--begin::Vendors Javascript(used for this page only)-->
    <script src="../assets/plugins/custom/datatables/datatables.bundle.js"></script>
    <!--end::Vendors Javascript-->
    
    <!--begin::SweetAlert2-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!--end::SweetAlert2-->
    
    <!--begin::jQuery Mask Plugin-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <!--end::jQuery Mask Plugin-->
    
    <!--begin::Custom Javascript-->
    <script>
        // Sistema de Troca de Tema
        (function() {
            var themeMode = localStorage.getItem("data-bs-theme") || "light";
            
            // Função para aplicar o tema
            function setTheme(mode) {
                var savedMode = mode;
                if (mode === "system") {
                    mode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
                }
                document.documentElement.setAttribute("data-bs-theme", mode);
                localStorage.setItem("data-bs-theme", savedMode);
                
                // Atualiza ícones
                if (mode === "dark") {
                    $('.theme-light-show').hide();
                    $('.theme-dark-show').show();
                } else {
                    $('.theme-light-show').show();
                    $('.theme-dark-show').hide();
                }
            }
            
            // Aplica tema inicial após jQuery estar carregado
            $(document).ready(function() {
                setTheme(themeMode);
                
                // Listener para mudanças no sistema
                if (themeMode === "system") {
                    window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", function() {
                        setTheme("system");
                    });
                }
                
                // Handler para cliques no menu de tema
                $(document).on('click', '[data-kt-element="mode"]', function(e) {
                    e.preventDefault();
                    themeMode = $(this).attr('data-kt-value');
                    setTheme(themeMode);
                });
            });
        })();
        
        // Inicializa DataTables em tabelas com classe .datatable (apenas se não tiverem ID específico)
        $(document).ready(function() {
            $('.datatable').each(function() {
                // Só inicializa se não tiver sido inicializado e não tiver ID específico
                if (!$(this).hasClass('dataTable') && !$(this).attr('id')) {
                    $(this).DataTable({
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                        },
                        pageLength: 25,
                        order: [[0, 'desc']],
                        responsive: true,
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
                    });
                }
            });
        });
    </script>
    <!--end::Custom Javascript-->
</body>
</html>
