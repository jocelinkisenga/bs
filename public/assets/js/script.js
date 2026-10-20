/*
Author       : Dreamguys
Template Name: POS - Bootstrap Admin Template
*/


$(document).ready(function(){

	// Variables declarations
	var $wrapper = $('.main-wrapper');
	var $slimScrolls = $('.slimscroll');
	var $pageWrapper = $('.page-wrapper');
	feather.replace();

	// Page Content Height Resize
	$(window).resize(function () {
		if ($('.page-wrapper').length > 0) {
			var height = $(window).height();
			$(".page-wrapper").css("min-height", height);
		}
	});

	// Mobile menu sidebar overlay
	$('body').append('<div class="sidebar-overlay"></div>');
	$(document).on('click', '#mobile_btn', function() {
		$wrapper.toggleClass('slide-nav');
		$('.sidebar-overlay').toggleClass('opened');
		$('html').addClass('menu-opened');
		$('#task_window').removeClass('opened');
		return false;
	});

	$(".sidebar-overlay").on("click", function () {
		$('html').removeClass('menu-opened');
		$(this).removeClass('opened');
		$wrapper.removeClass('slide-nav');
		$('.sidebar-overlay').removeClass('opened');
		$('#task_window').removeClass('opened');
	});

	// Logo Hide Btn

	$(document).on("click",".hideset",function () {
		$(this).parent().parent().parent().hide();
	});

	$(document).on("click",".delete-set",function () {
		$(this).parent().parent().hide();
	});

	// Owl Carousel
	if($('.product-slide').length > 0 ){
		$('.product-slide').owlCarousel({
			items: 1,
			margin: 30,
			dots : false,
			nav: true,
			loop: false,
			responsiveClass:true,
			responsive: {
				0: {
					items: 1
				},
				800 : {
					items: 1
				},
				1170: {
					items: 1
				}
			}
		});
	}

	//Home popular 
	if($('.owl-product').length > 0 ){
		var owl = $('.owl-product');
			owl.owlCarousel({
			margin: 10,
			dots : false,
			nav: true,
			loop: false,
			touchDrag:false,
			mouseDrag  : false,
			responsive: {
				0: {
					items: 2
				},
				768 : {
					items: 4
				},
				1170: {
					items: 8
				}
			}
		});
	}
	// Datatable
	if($('.datanew').length > 0) {
		$('.datanew').DataTable({
			"bFilter": true,
			"sDom": 'fBtlpi',  
			'pagingType': 'numbers', 
			"ordering": true,
			"language": {
				search: ' ',
				sLengthMenu: '_MENU_',
				searchPlaceholder: "Search...",
				info: "_START_ - _END_ of _TOTAL_ items",
			 },
			initComplete: (settings, json)=>{
				$('.dataTables_filter').appendTo('#tableSearch');
				$('.dataTables_filter').appendTo('.search-input');
			},	
		});
	}

	// image file upload image
	function readURL(input) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();
	
			reader.onload = function (e) {
				$('#blah').attr('src', e.target.result);
			}
	
			reader.readAsDataURL(input.files[0]);
		}
	}
	
	$("#imgInp").change(function(){
		readURL(this);
	});


	if($('.datatable').length > 0) {
		$('.datatable').DataTable({
			"bFilter": false
		});
	}
	// Loader
	setTimeout(function () {
		$('#global-loader');
		setTimeout(function () {
			$("#global-loader").fadeOut("slow");
		}, 100);
	}, 500);

	// Datetimepicker
	if($('.datetimepicker').length > 0 ){
		$('.datetimepicker').datetimepicker({
			format: 'DD-MM-YYYY',
			icons: {
				up: "fas fa-angle-up",
				down: "fas fa-angle-down",
				next: 'fas fa-angle-right',
				previous: 'fas fa-angle-left'
			}
		});
	}
	
	// toggle-password
	if($('.toggle-password').length > 0) {
		$(document).on('click', '.toggle-password', function() {
			$(this).toggleClass("fa-eye fa-eye-slash");
			var input = $(".pass-input");
			if (input.attr("type") == "password") {
				input.attr("type", "text");
			} else {
				input.attr("type", "password");
			}
		});
	}
	if($('.toggle-passwords').length > 0) {
		$(document).on('click', '.toggle-passwords', function() {
			$(this).toggleClass("fa-eye fa-eye-slash");
			var input = $(".pass-inputs");
			if (input.attr("type") == "password") {
				input.attr("type", "text");
			} else {
				input.attr("type", "password");
			}
		}); 
	}
	if($('.toggle-passworda').length > 0) {
		$(document).on('click', '.toggle-passworda', function() {
			$(this).toggleClass("fa-eye fa-eye-slash");
			var input = $(".pass-inputs");
			if (input.attr("type") == "password") {
				input.attr("type", "text");
			} else {
				input.attr("type", "password");
			}
		});
	}

	// Select 2
	if ($('.select').length > 0) {
		$('.select').select2({
			minimumResultsForSearch: -1,
			width: '100%'
		});
	}

	// Counter 
	if($('.counter').length > 0) {
		$('.counter').counterUp({
			delay: 20,
			time: 2000
		});
	}
	if($('#timer-countdown').length > 0) {
		$( '#timer-countdown' ).countdown( {
			from: 180, // 3 minutes (3*60)
			to: 0, // stop at zero
			movingUnit: 1000, // 1000 for 1 second increment/decrements
			timerEnd: undefined,
			outputPattern: '$day Day $hour : $minute : $second',
			autostart: true
		});
	}
	
	if($('#timer-countup').length > 0) {
		$( '#timer-countup' ).countdown( {
			from: 0,
			to: 180 
		});
	}
	
	if($('#timer-countinbetween').length > 0) {
		$( '#timer-countinbetween' ).countdown( {
			from: 30,
			to: 20 
		});
	}
	
	if($('#timer-countercallback').length > 0) {
		$( '#timer-countercallback' ).countdown( {
			from: 10,
			to: 0,
			timerEnd: function() {
				this.css( { 'text-decoration':'line-through' } ).animate( { 'opacity':.5 }, 500 );
			} 
		});
	}
	
	if($('#timer-outputpattern').length > 0) {
		$( '#timer-outputpattern' ).countdown( {
			outputPattern: '$day Days $hour Hour $minute Min $second Sec..',
			from: 60 * 60 * 24 * 3
		});
	}

	// Summernote

	if($('#summernote').length > 0) {
		$('#summernote').summernote({
		height: 300,                 // set editor height
		minHeight: null,             // set minimum height of editor
		maxHeight: null,             // set maximum height of editor
		focus: true                  // set focus to editable area after initializing summernote
		});
	}
	


	// Sidebar Slimscroll
	if($slimScrolls.length > 0) {
		$slimScrolls.slimScroll({
			height: 'auto',
			width: '100%',
			position: 'right',
			size: '7px',
			color: '#ccc',
			wheelStep: 10,
			touchScrollStep: 100
		});
		var wHeight = $(window).height() - 60;
		$slimScrolls.height(wHeight);
		$('.sidebar .slimScrollDiv').height(wHeight);
		$(window).resize(function() {
			var rHeight = $(window).height() - 60;
			$slimScrolls.height(rHeight);
			$('.sidebar .slimScrollDiv').height(rHeight);
		});
	}

	// Sidebar
	var Sidemenu = function() {
		this.$menuItem = $('#sidebar-menu a');
	};

	function init() {
		var $this = Sidemenu;
		$('#sidebar-menu a').on('click', function(e) {
			if($(this).parent().hasClass('submenu')) {
				e.preventDefault();
			}
			if(!$(this).hasClass('subdrop')) {
				$('ul', $(this).parents('ul:first')).slideUp(250);
				$('a', $(this).parents('ul:first')).removeClass('subdrop');
				$(this).next('ul').slideDown(350);
				$(this).addClass('subdrop');
			} else if($(this).hasClass('subdrop')) { 
				$(this).removeClass('subdrop');
				$(this).next('ul').slideUp(350);
			}
		});
		$('#sidebar-menu ul li.submenu a.active').parents('li:last').children('a:first').addClass('active').trigger('click');
	}
	
	// Sidebar Initiate
	init();
	$(document).on('mouseover', function(e) {
        e.stopPropagation();
        if ($('body').hasClass('mini-sidebar') && $('#toggle_btn').is(':visible')) {
            var targ = $(e.target).closest('.sidebar, .header-left').length;
            if (targ) {
                $('body').addClass('expand-menu');
                $('.subdrop + ul').slideDown();
            } else {
                $('body').removeClass('expand-menu');
                $('.subdrop + ul').slideUp();
            }
            return false;
        }
    });

	//toggle_btn
	$(document).on('click', '#toggle_btn', function() {
		if ($('body').hasClass('mini-sidebar')) {
			$('body').removeClass('mini-sidebar');
			$(this).addClass('active');
			$('.subdrop + ul');
			localStorage.setItem('screenModeNightTokenState', 'night');
			setTimeout(function() {
				$("body").removeClass("mini-sidebar");
				$(".header-left").addClass("active");
			}, 100);
		} else {
			$('body').addClass('mini-sidebar');
			$(this).removeClass('active');
			$('.subdrop + ul');
			localStorage.removeItem('screenModeNightTokenState', 'night');
			setTimeout(function() {
				$("body").addClass("mini-sidebar");
				$(".header-left").removeClass("active");
			}, 100);
		}
		return false;
	});


	if (localStorage.getItem('screenModeNightTokenState') == 'night') {
		setTimeout(function() {
			$("body").removeClass("mini-sidebar");
			$(".header-left").addClass("active");
		}, 100);
	}

	$('.submenus').on('click', function(){
		$('body').addClass('sidebarrightmenu');
	});
	
	$('#searchdiv').on('click', function(){
		$('.searchinputs').addClass('show');
	});
	$('.search-addon span').on('click', function(){
		$('.searchinputs').removeClass('show');
	});
	$(document).on('click', '#filter_search', function() {
		$('#filter_inputs').slideToggle("slow");
	});
	$(document).on('click', '#filter_search1', function() {
		$('#filter_inputs1').slideToggle("slow");
	});
	$(document).on('click', '#filter_search2', function() {
		$('#filter_inputs2').slideToggle("slow");
	});
	$(document).on('click', '#filter_search', function() {
		$('#filter_search').toggleClass("setclose");
	});
	$(document).on("click",".productset",function () {
		$(this).toggleClass("active");
	});



	//Increment Decrement value
	$('.inc.button').click(function(){
	    var $this = $(this),
	        $input = $this.prev('input'),
	        $parent = $input.closest('div'),
	        newValue = parseInt($input.val())+1;
	    $parent.find('.inc').addClass('a'+newValue);
	    $input.val(newValue);
	    newValue += newValue;
	});
	$('.dec.button').click(function(){
	    var $this = $(this),
	        $input = $this.next('input'),
	        $parent = $input.closest('div'),
	        newValue = parseInt($input.val())-1;
	    console.log($parent);
	    $parent.find('.inc').addClass('a'+newValue);
	    $input.val(newValue);
	    newValue += newValue;
	});

	if($('.custom-file-container').length > 0) {
		//First upload
		var firstUpload = new FileUploadWithPreview('myFirstImage')
		//Second upload
		var secondUpload = new FileUploadWithPreview('mySecondImage')
	}

	$('.counters').each(function() {
		var $this = $(this),
			countTo = $this.attr('data-count');
		$({ countNum: $this.text()}).animate({
			countNum: countTo
		},
		{
			duration: 2000,
			easing:'linear',
			step: function() {
			$this.text(Math.floor(this.countNum));
			},
			complete: function() {
			$this.text(this.countNum);
			}
		
		});  
		
	});
	

	// toggle-password
	if($('.toggle-password').length > 0) {
		$(document).on('click', '.toggle-password', function() {
			$(this).toggleClass("fa-eye fa-eye");
			var input = $(".pass-input");
			if (input.attr("type") == "text") {
				input.attr("type", "text");
			} else {
				input.attr("type", "password");
			}
		});
	}


	if($('.win-maximize').length > 0) {
		$('.win-maximize').on('click', function(e){
			if (!document.fullscreenElement) {
				document.documentElement.requestFullscreen();
			} else {
				if (document.exitFullscreen) {
					document.exitFullscreen();
				}
			}
		})
	}


	$(document).on('click', '#check_all', function() {
		$('.checkmail').click();
		return false;
	});
	if($('.checkmail').length > 0) {
		$('.checkmail').each(function() {
			$(this).on('click', function() {
				if($(this).closest('tr').hasClass('checked')) {
					$(this).closest('tr').removeClass('checked');
				} else {
					$(this).closest('tr').addClass('checked');
				}
			});
		});
	}
		
	// Popover
	if($('.popover-list').length > 0) {
		var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
		var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
		return new bootstrap.Popover(popoverTriggerEl)
		})
	}

	// Clipboard 
	if($('.clipboard').length > 0) {
		var clipboard = new Clipboard('.btn');
	}

	// Chat
	var chatAppTarget = $('.chat-window');
	(function() {
		if ($(window).width() > 991)
			chatAppTarget.removeClass('chat-slide');
		
		$(document).on("click",".chat-window .chat-users-list a.media",function () {
			if ($(window).width() <= 991) {
				chatAppTarget.addClass('chat-slide');
			}
			return false;
		});
		$(document).on("click","#back_user_list",function () {
			if ($(window).width() <= 991) {
				chatAppTarget.removeClass('chat-slide');
			}	
			return false;
		});
	})();

	// Mail important
	
	$(document).on('click', '.mail-important', function() {
		$(this).find('i.fa').toggleClass('fa-star').toggleClass('fa-star-o');
	});

		
	var selectAllItems = "#select-all";
	var checkboxItem = ":checkbox";
	$(selectAllItems).click(function() {
		
		if (this.checked) {
		$(checkboxItem).each(function() {
			this.checked = true;
		});
		} else {
		$(checkboxItem).each(function() {
			this.checked = false;
		});
		}
		
	});
		
	// Tooltip
	if($('[data-bs-toggle="tooltip"]').length > 0) {
		var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
		var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
			return new bootstrap.Tooltip(tooltipTriggerEl)
		})
	}

	var right_side_views = '<div class="right-side-views d-none">' +
	'<ul class="sticky-sidebar siderbar-view">' +
		'<li class="sidebar-icons">' +
			'<a class="toggle tipinfo open-layout open-siderbar" href="javascript:void(0);" data-toggle="tooltip" data-placement="left" data-bs-original-title="Tooltip on left">' +
				'<div class="tooltip-five ">' +
					'<img src="assets/img/icons/siderbar-icon2.svg" class="feather-five" alt="">' +
					'<span class="tooltiptext">Check Layout</span>' +
				'</div>' +
			'</a>' +
		'</li>' +
	'</ul>' +
'</div>' +

'<div class="sidebar-layout">' +
	'<div class="sidebar-content">' +
		'<div class="sidebar-top">' +
			'<div class="container-fluid">' +
				'<div class="row align-items-center">' +
					'<div class="col-xl-6 col-sm-6 col-12">' +
						'<div class="sidebar-logo">' +
							'<a href="index.html" class="logo">' +
								'<img src="assets/img/logo.png" alt="Logo" class="img-flex">' +
							'</a>' +
						'</div>' +
					'</div>' +
					'<div class="col-xl-6 col-sm-6 col-12">' +
						'<a class="btn-closed" href="javascript:void(0);"><img class="img-fliud" src="assets/img/icons/sidebar-delete-icon.svg" alt="demo"></a>' +
					'</div>' +
				'</div>' +
			'</div>' +
		'</div>' +
		'<div class="container-fluid">' +
			'<div class="row align-items-center">' +
				'<h5 class="sidebar-title">Choose layout</h5>' +
				'<div class="col-xl-12 col-sm-6 col-12">' +
					'<div class="sidebar-image align-center">' +
						'<img class="img-fliud" src="assets/img/demo-one.png" alt="demo">' +
					'</div>' +
					'<div class="row">' +
						'<div class="col-lg-6 layout">' +
							'<h5 class="layout-title">Dark Mode</h5>' +
						'</div>' +
						'<div class="col-lg-6 layout dark-mode">' +
							'<label class="toggle-switch" for="notification_switch3">' +
							'<span>' +
							'<input type="checkbox" class="toggle-switch-input" id="notification_switch3">' +
							'<span class="toggle-switch-label ms-auto">' +
							'	<span class="toggle-switch-indicator"></span>' +
							'</span>' +
							'</span>' +
							' </label>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>' +
		'</div>' +
	'</div>' +
	'</div>' +
    $("body").append(right_side_views);

	// Sidebar Visible
	
	$('.open-layout').on("click", function (s) {
		s.preventDefault();
		$('.sidebar-layout').addClass('show-layout');
		$('.sidebar-settings').removeClass('show-settings');
	});
	$('.btn-closed').on("click", function (s) {
		s.preventDefault();
		$('.sidebar-layout').removeClass('show-layout');
	});
	$('.open-settings').on("click", function (s) {
		s.preventDefault();
		$('.sidebar-settings').addClass('show-settings');
		$('.sidebar-layout').removeClass('show-layout');
	});

	$('.btn-closed').on("click", function (s) {
		s.preventDefault();
		$('.sidebar-settings').removeClass('show-settings');
	});

	$('.open-siderbar').on("click", function (s) {
		s.preventDefault();
		$('.siderbar-view').addClass('show-sidebar');
	});

	$('.btn-closed').on("click", function (s) {
		s.preventDefault();
		$('.siderbar-view').removeClass('show-sidebar');
	});

	if($('.toggle-switch').length > 0) {
		const toggleSwitch = document.querySelector('.toggle-switch input[type="checkbox"]');
		const currentTheme = localStorage.getItem('theme');
		var app = document.getElementsByTagName("BODY")[0];

		if (currentTheme) {
			app.setAttribute('data-theme', currentTheme);
		  
			if (currentTheme === 'dark') {
				toggleSwitch.checked = true;
			}
		}

		function switchTheme(e) {
			if (e.target.checked) {
				app.setAttribute('data-theme', 'dark');
				localStorage.setItem('theme', 'dark');
			}
			else {       
				app.setAttribute('data-theme', 'light');
				localStorage.setItem('theme', 'light');
			}    
		}

		toggleSwitch.addEventListener('change', switchTheme, false);	
	}
	
	if(window.location.hash == "#LightMode"){
		localStorage.setItem('theme', 'dark');
	}
	else {
		if(window.location.hash == "#DarkMode"){
			localStorage.setItem('theme', 'light');
		}
	}

	
	$('ul.tabs li').click(function(){
		var $this = $(this);
		var $theTab = $(this).attr('id');
		console.log($theTab);
		if($this.hasClass('active')){
		  // do nothing
		} else{
		  $this.closest('.tabs_wrapper').find('ul.tabs li, .tabs_container .tab_content').removeClass('active');
		  $('.tabs_container .tab_content[data-tab="'+$theTab+'"], ul.tabs li[id="'+$theTab+'"]').addClass('active');
		}
		
	});
	  

});

// categories
/* =====================================================
   SELLIT · Catégories
   Fichier statique chargé une fois par le layout.
   ===================================================== */

(function () {
    function getModal() {
        var el = document.getElementById('categorieModal');
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    // Déclenché par $this->dispatch('open-categorie-modal')
    window.addEventListener('open-categorie-modal', function () {
        var modal = getModal();
        if (modal) modal.show();

        // Focus sur le champ après l'ouverture
        var el = document.getElementById('categorieModal');
        if (el) {
            el.addEventListener('shown.bs.modal', function focusOnce() {
                var input = document.getElementById('categorie-name');
                if (input) input.focus();
                el.removeEventListener('shown.bs.modal', focusOnce);
            });
        }
    });

    // Déclenché par $this->dispatch('categorie-saved')
    window.addEventListener('categorie-saved', function () {
        var modal = getModal();
        if (modal) modal.hide();
    });

    // Confirmation avant suppression, puis appel Livewire
    window.confirmerSuppressionCategorie = function (button) {
        var id = Number(button.dataset.id);
        var nom = button.dataset.name;
        var root = button.closest('[wire:id]');

        if (!root) return;

        Swal.fire({
            title: 'Supprimer la catégorie ?',
            text: '« ' + nom + ' » sera supprimée définitivement.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var component = window.Livewire.find(root.getAttribute('wire:id'));
            component.call('delete', id);
        });
    };
})();




// products
/* =====================================================
   SELLIT · Produits
   Fichier statique chargé une fois par le layout.
   ===================================================== */

(function () {
    var MODAL_ID = 'produitModal';
    var INPUT_ID = 'produit-name';

    function getModal() {
        var el = document.getElementById(MODAL_ID);
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    // Ouverture du modal (dispatch Livewire : open-produit-modal)
    window.addEventListener('open-produit-modal', function () {
        var modal = getModal();
        if (modal) modal.show();
    });

    // Fermeture après enregistrement (dispatch Livewire : produit-saved)
    window.addEventListener('produit-saved', function () {
        var modal = getModal();
        if (modal) modal.hide();
    });

    // Focus sur le champ nom à chaque ouverture
    document.addEventListener('shown.bs.modal', function (e) {
        if (e.target.id !== MODAL_ID) return;
        var input = document.getElementById(INPUT_ID);
        if (input) input.focus();
    });

    // Confirmation avant suppression, puis appel Livewire
    window.confirmerSuppressionProduit = function (button) {
        var id = Number(button.dataset.id);
        var nom = button.dataset.name;
        var root = button.closest('[wire\\:id]');

        if (!root) return;

        Swal.fire({
            title: 'Supprimer le produit ?',
            text: '« ' + nom + ' » sera supprimé définitivement.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var component = window.Livewire.find(root.getAttribute('wire:id'));
            component.call('delete', id);
        });
    };
})();


// tables
/* =====================================================
   SELLIT · Tables
   Fichier statique chargé une fois par le layout.
   ===================================================== */

(function () {
    var MODAL_ID = 'tableModal';
    var INPUT_ID = 'table-name';

    function getModal() {
        var el = document.getElementById(MODAL_ID);
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    // Dispatch Livewire : open-table-modal
    window.addEventListener('open-table-modal', function () {
        var modal = getModal();
        if (modal) modal.show();
    });

    // Dispatch Livewire : table-saved
    window.addEventListener('table-saved', function () {
        var modal = getModal();
        if (modal) modal.hide();
    });

    // Focus sur le champ nom à chaque ouverture
    document.addEventListener('shown.bs.modal', function (e) {
        if (e.target.id !== MODAL_ID) return;
        var input = document.getElementById(INPUT_ID);
        if (input) input.focus();
    });

    // Confirmation avant suppression, puis appel Livewire
    window.confirmerSuppressionTable = function (button) {
        var id = Number(button.dataset.id);
        var nom = button.dataset.name;
        var root = button.closest('[wire\\:id]');

        if (!root) return;

        Swal.fire({
            title: 'Supprimer la table ?',
            text: '« ' + nom + ' » sera supprimée définitivement.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var component = window.Livewire.find(root.getAttribute('wire:id'));
            component.call('delete', id);
        });
    };
})();


//depenses
/* =====================================================
   SELLIT · Dépenses
   Fichier statique chargé une fois par le layout.
   ===================================================== */

(function () {
    var MODAL_ID = 'depenseModal';
    var INPUT_ID = 'dep-montant';

    function getModal() {
        var el = document.getElementById(MODAL_ID);
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    // Dispatch Livewire : open-depense-modal
    window.addEventListener('open-depense-modal', function () {
        var modal = getModal();
        if (modal) modal.show();
    });

    // Dispatch Livewire : depense-saved
    window.addEventListener('depense-saved', function () {
        var modal = getModal();
        if (modal) modal.hide();
    });

    // Focus sur le montant à chaque ouverture
    document.addEventListener('shown.bs.modal', function (e) {
        if (e.target.id !== MODAL_ID) return;
        var input = document.getElementById(INPUT_ID);
        if (input) input.focus();
    });

    // Confirmation avant suppression, puis appel Livewire
    window.confirmerSuppressionDepense = function (button) {
        var id = Number(button.dataset.id);
        var libelle = button.dataset.label;
        var root = button.closest('[wire\\:id]');

        if (!root) return;

        Swal.fire({
            title: 'Supprimer la dépense ?',
            text: '« ' + libelle + ' » sera supprimée définitivement.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var component = window.Livewire.find(root.getAttribute('wire:id'));
            component.call('delete', id);
        });
    };
})();

//users
/* =====================================================
   SELLIT · Personnel
   Fichier statique chargé une fois par le layout.
   ===================================================== */

(function () {
    var MODAL_ID = 'userModal';
    var INPUT_ID = 'usr-name';

    function getModal() {
        var el = document.getElementById(MODAL_ID);
        return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    // Dispatch Livewire : open-user-modal
    window.addEventListener('open-user-modal', function () {
        var modal = getModal();
        if (modal) modal.show();
    });

    // Dispatch Livewire : user-saved
    window.addEventListener('user-saved', function () {
        var modal = getModal();
        if (modal) modal.hide();
    });

    // Focus sur le nom à chaque ouverture
    document.addEventListener('shown.bs.modal', function (e) {
        if (e.target.id !== MODAL_ID) return;
        var input = document.getElementById(INPUT_ID);
        if (input) input.focus();
    });

    // Confirmation avant suppression, puis appel Livewire
    window.confirmerSuppressionUser = function (button) {
        var id = Number(button.dataset.id);
        var nom = button.dataset.name;
        var root = button.closest('[wire\\:id]');

        if (!root) return;

        Swal.fire({
            title: 'Supprimer ce membre ?',
            text: '« ' + nom + ' » ne pourra plus se connecter.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            var component = window.Livewire.find(root.getAttribute('wire:id'));
            component.call('delete', id);
        });
    };
})();

	





// dashboard
/* =====================================================
   SELLIT · Tableau de bord financier (graphiques)
   Chart.js doit être chargé avant ce fichier.
   ===================================================== */

(function () {
    var ROOT_ID = 'rv-root';
    var DATA_ID = 'rv-data';

    var charts = {};
    var derniere = null;
    var enAttente = false;
    var brancheLivewire = false;

    var C = {
        primary: '#4f46e5',
        cr: '#b45309',
        dep: '#dc2626',
        ink: '#64748b',
        grid: '#eef2f7',
        faible: 'rgba(79, 70, 229, .25)'
    };
    var PALETTE = ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

    function el(id) { return document.getElementById(id); }

    function fc(n) { return Number(n).toLocaleString('fr-FR') + ' Fc'; }

    function axes() {
        return {
            x: { grid: { display: false }, ticks: { color: C.ink } },
            y: { grid: { color: C.grid }, ticks: { color: C.ink } }
        };
    }

    function detruire(cle) {
        if (charts[cle]) {
            charts[cle].destroy();
            delete charts[cle];
        }
    }

    function lire() {
        var node = el(DATA_ID);
        if (!node) return null;
        try {
            return JSON.parse(node.getAttribute('data-charts'));
        } catch (e) {
            return null;
        }
    }

    function dessiner(d) {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js n\'est pas chargé.');
            return;
        }

        /* Évolution : CA net, créances, dépenses */
        detruire('evolution');
        if (el('rv-evolution')) {
            charts.evolution = new Chart(el('rv-evolution'), {
                type: 'line',
                data: {
                    labels: d.evolution.labels,
                    datasets: [
                        {
                            label: 'CA net', data: d.evolution.net,
                            borderColor: C.primary, backgroundColor: 'rgba(79, 70, 229, .10)',
                            fill: true, tension: .35, pointRadius: 2, borderWidth: 2.5
                        },
                        {
                            label: 'Créances', data: d.evolution.cr,
                            borderColor: C.cr, tension: .35, pointRadius: 0, borderWidth: 2
                        },
                        {
                            label: 'Dépenses', data: d.evolution.dep,
                            borderColor: C.dep, borderDash: [6, 4], tension: .35, pointRadius: 0, borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: {
                            callbacks: {
                                label: function (c) { return c.dataset.label + ' : ' + fc(c.parsed.y); }
                            }
                        }
                    },
                    scales: axes()
                }
            });
        }

        /* CA net par jour de semaine : le meilleur jour est mis en avant */
        detruire('jours');
        if (el('rv-jours')) {
            var max = Math.max.apply(null, d.jours.values.concat([0]));
            charts.jours = new Chart(el('rv-jours'), {
                type: 'bar',
                data: {
                    labels: d.jours.labels,
                    datasets: [{
                        label: 'CA net',
                        data: d.jours.values,
                        borderRadius: 8,
                        backgroundColor: d.jours.values.map(function (v) {
                            return v === max && v > 0 ? C.primary : C.faible;
                        })
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: function (c) { return fc(c.parsed.y); } } }
                    },
                    scales: axes()
                }
            });
        }

        /* Top 5 produits */
        detruire('produits');
        if (el('rv-produits') && d.produits.labels.length) {
            charts.produits = new Chart(el('rv-produits'), {
                type: 'bar',
                data: {
                    labels: d.produits.labels,
                    datasets: [{
                        label: 'CA brut',
                        data: d.produits.values,
                        backgroundColor: PALETTE,
                        borderRadius: 8
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: function (c) { return fc(c.parsed.x); } } }
                    },
                    scales: axes()
                }
            });
        }
    }

    function mettreAJour() {
        enAttente = false;

        var donnees = lire();
        if (!donnees) return;

        var signature = JSON.stringify(donnees);
        if (signature === derniere) return;

        derniere = signature;
        dessiner(donnees);
    }

    function planifier() {
        if (enAttente) return;
        enAttente = true;
        requestAnimationFrame(mettreAJour);
    }

    function brancherLivewire() {
        if (brancheLivewire || !window.Livewire || typeof window.Livewire.hook !== 'function') {
            return false;
        }

        brancheLivewire = true;

        window.Livewire.hook('morph.updated', function (payload) {
            var root = el(ROOT_ID);
            if (root && payload && payload.el && root.contains(payload.el)) {
                planifier();
            }
        });

        return true;
    }

    document.addEventListener('livewire:init', brancherLivewire);

    document.addEventListener('DOMContentLoaded', function () {
        brancherLivewire();
        planifier();
    });
})();