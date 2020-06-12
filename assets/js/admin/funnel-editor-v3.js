( function ($, funnel, modal) {

    $.extend(funnel, {

        sortables: null,

        getSteps: function () {
            return $('#step-sortable');
        },

        getSettings: function () {
            return $('.step-settings');
        },

        init: function () {

            var self = this;

            var $document = $(document);
            var $form = $('#funnel-form');
            var $steps = self.getSteps();
            var $settings = self.getSettings();

            $document.on('change input', '.step-title-large', function () {
                var $title = $(this);
                var id = $title.attr('data-id');
                var $step = $('#' + id);
                $step.find('.step-title').text($title.val());
            });

            $document.on('click', '#postbox-container-1 .step', function (e) {
                self.makeActive(this.id, e);
            });

            $document.on('click', 'td.step-icon', function (e) {

                var $activeStep = $steps.find('.active');
                $('<div class="replace-me"></div>').
                    insertAfter($activeStep);

                var $icon = $(this);
                var $type = $icon.find('.wpgh-element');
                var type = $type.attr('id');
                var order = $steps.index($activeStep) + 1;

                var data = {
                    action: 'wpgh_get_step_html',
                    step_type: type,
                    after_step: $activeStep.attr('id'),
                    funnel_id: self.id,
                    version: 2,
                };

                self.getStepHtml(data);

            });

            /* Bind Delete */
            $document.on('click', 'button.delete-step', function (e) {
                self.deleteStep(this.parentNode.parentNode.id);
            });

            /* Bind Duplicate */
            $document.on('click', 'button.duplicate-step', function (e) {
                self.duplicateStep(this.parentNode.parentNode.id);
            });

            /* Activate Spinner */
            $form.on('submit', function (e) {
                e.preventDefault();
                self.save($form);
            });

            /* Auto save */
            $document.on('change', '.auto-save', function (e) {
                e.preventDefault();
                self.save($form);
            });

            /* Auto save */
            $document.on('auto-save', function (e) {
                e.preventDefault();
                self.save($form);
            });

            // Funnel Title
            $document.on('click', '.title-view .title', function (e) {
                $('.title-view').hide();
                $('.title-edit').show().removeClass('hidden');
                $('#title').focus();
            });

            $document.on('blur change', '#title', function (e) {

                var title = $(this).val();

                $('.title-view').find('.title').text(title);
                $('.title-view').show();
                $('.title-edit').hide();
            });

            // Step Title
            $document.on('click', '.step-title-view .title', function (e) {

                var $step = $(this).closest('.step');

                $step.find('.step-title-view').hide();
                $step.find('.step-title-edit').show().removeClass('hidden');
                $step.find('.step-title-edit .edit-title').focus();
            });

            $document.on('blur change', '.edit-title', function (e) {

                var $step = $(this).closest('.step');

                var title = $(this).val();

                $step.find('.step-title-view').find('.title').text(title);
                $step.find('.step-title-view').show();
                $step.find('.step-title-edit').hide();
            });

            $document.on('click', '#enter-full-screen', function (e) {
                $('html').toggleClass('full-screen');
            });

            if (window.innerWidth > 600) {
                this.makeSortable();
            }

            $('#add-contacts-button').click(function () {
                self.addContacts();
            });

            $('#copy-share-link').click(function (e) {
                e.preventDefault();
                prompt('Copy this link.', $('#share-link').val());
            });

        },



        save: function ($form) {

            if (typeof $form === 'undefined') {
                $form = $('#funnel-form');
            }

            var self = this;

            var $saveButton = $('.save-button');

            $('body').addClass('saving');

            $saveButton.html(self.saving_text);
            $saveButton.addClass('spin');

            var fd = $form.serialize();
            fd = fd + '&action=gh_save_funnel_via_ajax&version=2';

            adminAjaxRequest(fd, function (response) {
                handleNotices(response.data.notices);

                setTimeout(function () {
                    $('.notice-success').fadeOut();
                }, 3000);

                $saveButton.removeClass('spin');
                $saveButton.html(self.save_text);

                self.getSettings().html(response.data.data.settings);
                self.getSteps().html(response.data.data.sortable);

                $(document).trigger('new-step');

                $('body').removeClass('saving');
            });
        },

        makeSortable: function () {
            this.sortables = $('.ui-sortable').sortable({
                placeholder: 'sortable-placeholder',
                connectWith: '.ui-sortable',
                axis: 'y',
                start: function (e, ui) {
                    ui.helper.css('left',
                        ( ui.item.parent().width() - ui.item.width() ) / 2);
                    ui.placeholder.height(ui.item.height());
                    ui.placeholder.width(ui.item.width());
                },
            });

            this.sortables.disableSelection();
        },

        /**
         * Given an element delete it
         *
         * @param id int
         */
        deleteStep: function (id) {

            var self = this;

            showSpinner();

            var $step = $('#' + id);
            var $prev_step = $step.prev();

            if ( ! $prev_step.attr( 'id' ) ){
                $prev_step = $step.next();

                if ( ! $prev_step.attr( 'id' ) ){
                    $prev_step = false;
                }
            }

            var result = confirm(
                'Are you sure you want to delete this step? Any contacts currently waiting will be moved to the next action.');

            if (result) {
                adminAjaxRequest(
                    { action: 'wpgh_delete_funnel_step', step_id: id },
                    function (result) {
                        hideSpinner();
                        $step.remove();
                        var sid = '#settings-' + id;
                        var $step_settings = $(sid);
                        $step_settings.remove();
                        $('html').removeClass('active-step');

                        if ( $prev_step !== false ){
                            self.makeActive( $prev_step.attr( 'id' ) );
                        }

                        self.save();
                    });
            }
            else {
                hideSpinner();
            }
        },

        /**
         * Given an element, duplicate the step and
         * Add it to the funnel
         *
         * @param id int
         */
        duplicateStep: function (id) {
            var $step = $('#' + id);
            $('<div class="replace-me"></div>').insertAfter($step);
            var data = {
                action: 'wpgh_duplicate_funnel_step',
                step_id: id,
                version: 2,
            };
            this.getStepHtml(data);
        },

        /**
         * Performs an ajax call and replaces
         *
         * @param obj
         */
        getStepHtml: function (obj) {
            var self = this;
            var $steps = self.getSteps();
            var $settings = self.getSettings();
            showSpinner();
            adminAjaxRequest(obj, function (response) {

                var $replaceable = $steps.find('.replace-me');

                if ($replaceable.length > 0) {
                    $replaceable.replaceWith(response.data.data.sortable);
                }
                else {
                    $steps.append(response.data.data.sortable);
                }

                $settings.append(response.data.data.settings);
                self.makeActive(response.data.data.id);
                modal.close();
                hideSpinner();
                $(document).trigger('new-step');
            });
        },

        /**
         * Make the given step active.
         *
         * @param id string
         * @param e object
         */
        makeActive: function (id, e) {
            var self = this;

            if (typeof e == 'undefined') {
                e = false;
            }

            var $steps = self.getSteps();
            var $settings = self.getSettings();
            var $html = $('html');

            var $step = $('#' + id);

            // If the click step was already active...
            var was_active = $step.hasClass('active');

            // In some cases we do not want to allow deselecting a step...
            if ( self.disable_deselect_step ){
                was_active = false;
            }

            // Remove active from the active step
            var make_inactive = true;

            if (e) {
                var $target = $(e.target);

                // console.log( e.target );

                if (was_active &&
                    ( $target.hasClass('dashicons') ||
                        $target.hasClass('add-step') )) {
                    // console.log( e )
                    make_inactive = false;
                }
            }

            if (make_inactive) {
                $settings.find('.step').addClass('hidden');
                $settings.find('.step').removeClass('active');
                $steps.find('.step').removeClass('active');
                $steps.find('.is_active').val(null);
                $html.removeClass('active-step');
            }

            // Make the clicked step active
            if (!was_active) {
                $step.addClass('active');
                $step.find('.is_active').val(1);

                var sid = '#settings-' + $step.attr('id');
                var $step_settings = $(sid);

                $step_settings.removeClass('hidden');
                $step_settings.addClass('active');
                $html.addClass('active-step');

                $(document).trigger('step-active');
            }
        },
    });

    $(function () {
        funnel.init();
    });

} )(jQuery, Funnel, GroundhoggModal);