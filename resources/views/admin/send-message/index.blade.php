@extends('layouts.admin')
@section('style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />

    <style>
        .telephone-field {
            position: relative
        }

        .telephone-field .iti {
            width: 100%
        }

        .telephone-field input {
            height: var(--ctl-h) !important;
            padding-left: 58px !important;
            /* room for flag */
        }

        /* search able select css start */
        .select2-container .select2-selection--single {
            height: 38px !important;
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #ccc !important;
            border-radius: 0px !important;
        }



        /* loading */
        /* Overlay Background */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            /* black with transparency */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            /* keep it above everything */
        }

        /* Rotating Loader Image */
        .loading-image {
            width: 50px;
            /* Adjust the size as needed */
            height: 50px;
            /* Adjust the size as needed */
            animation: rotateImage 2s linear infinite;
        }

        /* Keyframes for rotating the image */
        @keyframes rotateImage {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endsection
@section('content')
    {{-- Helpful when doing AJAX with jQuery --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-10 col-sm-12 col-12 mx-auto">
            <div class="section-block" id="basicform">
                <h3 class="section-title">Send Message</h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="sendTemplateForm" action="{{ url('admin/send-teplate/store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="old_customer">Select old customer</label>
                                <select id="old_customer" class="form-control select2">
                                    <option value="">---Select an older customer---</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" data-first_name="{{ $user->first_name }}"
                                            data-last_name="{{ $user->last_name }}" data-email="{{ $user->email }}"
                                            data-address="{{ $user->address }}" data-postal_code="{{ $user->postal_code }}"
                                            data-phone="{{ $user->contact }}">
                                            {{ $user->first_name }} {{ $user->last_name }} ({{ $user->contact }})
                                        </option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="form-group col-md-6">
                                <label for="template_id">Select Template</label>
                                <select name="template_id" id="template_id" class="form-control" required>
                                    <option value="">---Select Message Template---</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->template_id }}">{{ $template->title }} </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">

                            <!-- First Name -->
                            <div class="form-group col-md-6">
                                <label for="first_name">First Name</label>
                                <input id="first_name" type="text" name="first_name" class="form-control"
                                    value="{{ old('first_name') }}" required>
                            </div>

                            <!-- Last Name -->
                            <div class="form-group col-md-6">
                                <label for="last_name">Last Name</label>
                                <input id="last_name" type="text" name="last_name" class="form-control"
                                    value="{{ old('last_name') }}" required>
                            </div>

                            <!-- Email -->
                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" class="form-control"
                                    value="{{ old('email') }}" required>
                            </div>

                            <!-- Phone -->
                            <div class="form-group col-md-6">
                                <label for="phone">Phone</label>
                                <div class="telephone-field">
                                    <input id="phone" type="tel" name="phone" class="tel-input form-control"
                                        required>
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="form-group col-md-8">
                                <label for="address">Address</label>
                                <input id="address" type="text" name="address" class="form-control"
                                    value="{{ old('address') }}" required>
                            </div>

                            <!-- Postal Code -->
                            <div class="form-group col-md-4">
                                <label for="postal_code">Postal Code</label>
                                <input id="postal_code" type="text" name="postal_code" class="form-control"
                                    value="{{ old('postal_code') }}" required readonly>
                            </div>


                        </div>

                        <button id="submitBtn" type="" class="btn btn-primary mt-3">
                            <span class="btn-text">Send Message</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="overlay" style="display: none" id="overlay">
        <img src="{{ asset('assets/icons/Loading.png') }}" class="loading-image" alt="Loading...">
    </div>
@endsection
@section('script')
    {{-- jQuery + jQuery Validate + Toastr (CDNs for convenience) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    {{-- Google Places (replace YOUR_API_KEY) --}}

    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"></script>

    <script>
        $(document).ready(function() {
            $('body').on('click', '#submitBtn', function(e) {
                e.preventDefault();

                let first_name = $('#first_name');
                let last_name = $('#last_name');
                let email = $('#email');
                let phone = $('#phone');
                let address = $('#address');
                let postal_code = $('#postal_code');
                let template_id = $('#template_id');

                // --- Helper Function for Error ---
                function showError(field, message) {
                    toastr.error(message);
                    field.focus();
                    return false;
                }

                // --- Field Validations ---
                if (first_name.val().trim() === '') return showError(first_name, 'Please enter first name');
                if (last_name.val().trim() === '') return showError(last_name, 'Please enter last name');

                if (email.val().trim() === '') {
                    return showError(email, 'Please enter email');
                } else if (!/^\S+@\S+\.\S+$/.test(email.val())) {
                    return showError(email, 'Please enter a valid email');
                }

                if (phone.val().trim() === '') return showError(phone, 'Please enter phone number');

                // ✅ Check for invalid phone format using class
                if (phone.hasClass('is-invalid')) {
                    return showError(phone, 'Please enter a valid number');
                }

                if (address.val().trim() === '') return showError(address, 'Please enter address');
                if (postal_code.val().trim() === '') return showError(postal_code,
                    'Postal Code is required');
                if (template_id.val().trim() === '') return showError(template_id,
                    'Please select a template');

                $('#overlay').show();
                // ✅ All Good → Submit Form
                $('#sendTemplateForm').submit();
            });
        });
    </script>


    <script>
        let iti;

        function initIntlTel(id) {
            const input = document.querySelector("#" + id);
            if (!input || !window.intlTelInput) return;

            iti = window.intlTelInput(input, {
                // Make sure this URL is reachable on your page
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
                initialCountry: "GB",
                preferredCountries: ["GB", "US", "CA", "AU"],
                nationalMode: false, // keep +CC in the field
                autoPlaceholder: "polite", // show country-specific placeholder
                // GeoIP (optional)
                geoIpLookup: function(cb) {
                    fetch("https://ipapi.co/json")
                        .then(r => r.json())
                        .then(d => cb(d && d.country_code ? d.country_code : "GB"))
                        .catch(() => cb("GB"));
                },
            });

            const dial = () => `+${iti.getSelectedCountryData().dialCode}`;
            const iso2 = () => (iti.getSelectedCountryData().iso2 || "gb").toUpperCase();

            function ensurePrefix() {
                const code = dial();
                const v = input.value.trim();
                if (!v || !v.startsWith('+')) input.value = code + ' ';
                else if (!v.startsWith(code)) input.value = code + ' ' + v.replace(/^\+\d+\s*/, '');
            }

            function applyMasking() {
                const originalCursorPos = input.selectionStart;
                const originalValue = input.value;
                ensurePrefix();
                const prefix = dial();
                let nationalPart = '';
                if (input.value.startsWith(prefix)) {
                    nationalPart = input.value.substring(prefix.length).replace(/\D/g, '');
                } else {
                    nationalPart = input.value.replace(/\D/g, '');
                }

                // 3. Restrict length based on the country's placeholder
                const placeholder = input.placeholder || '';
                const maxNationalLen = (placeholder.substring(prefix.length).replace(/\D/g, '')).length;
                if (maxNationalLen > 0 && nationalPart.length > maxNationalLen) {
                    nationalPart = nationalPart.substring(0, maxNationalLen);
                }

                // 4. Re-format the number using the library's utils
                const numberToFormat = prefix + nationalPart;
                let formattedValue = numberToFormat; // Default to sanitized value if formatting fails
                try {
                    const formatted = window.intlTelInputUtils.formatNumber(
                        numberToFormat,
                        iso2(),
                        window.intlTelInputUtils.numberFormat.INTERNATIONAL
                    );
                    if (formatted) {
                        formattedValue = formatted;
                    }
                } catch (_) {
                    // Ignore formatting errors during typing
                }

                // 5. If the value changed, update the input and recalculate cursor position
                if (input.value === formattedValue) return;

                // Count digits before the original cursor to find its new logical position
                const digitsBeforeCursor = (originalValue.substring(0, originalCursorPos).replace(/\D/g, '')).length;

                input.value = formattedValue;

                let newCursorPos = 0;
                let digitsCounted = 0;
                for (const char of formattedValue) {
                    newCursorPos++;
                    if (/\d/.test(char)) {
                        digitsCounted++;
                    }
                    // Stop once we've passed the same number of digits
                    if (digitsCounted >= digitsBeforeCursor) {
                        break;
                    }
                }

                // Handle case where user deletes everything back to the prefix
                if (nationalPart.length === 0) {
                    newCursorPos = formattedValue.length;
                }

                input.setSelectionRange(newCursorPos, newCursorPos);
            }

            // Wait until utils.js is loaded before wiring formatting logic
            (iti.promise || Promise.resolve()).then(() => {
                // First paint
                ensurePrefix();

                input.addEventListener('focus', () => {
                    ensurePrefix();
                    // put caret at end
                    setTimeout(() => input.setSelectionRange(input.value.length, input.value.length), 0);
                });

                input.addEventListener('input', applyMasking);
                input.addEventListener('countrychange', () => {
                    ensurePrefix();
                    applyMasking();
                });
                input.addEventListener('blur', () => {

                    applyMasking();
                    const ok = (() => {
                        try {
                            return iti.isValidNumber();
                        } catch {
                            return true;
                        }
                    })();
                    input.classList.toggle('is-invalid', !ok);
                    input.dataset.phoneValid = ok ? '1' : '0';
                });
            });
        }

        initIntlTel('phone');
    </script>


    <script>
        // --- Toastr config ---
        (function initToastr() {
            if (!window.toastr) return;
            toastr.options = Object.assign({
                closeButton: true,
                newestOnTop: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 3500,
                preventDuplicates: true
            }, toastr.options || {});
        })();

        // --- Google Places: autocomplete for Address + fill Postal Code ---
        function initAddressAutocomplete() {
            if (!(window.google && google.maps && google.maps.places)) return;

            const addressField = document.getElementById('address');
            const postalField = document.getElementById('postal_code');
            if (!addressField) return;

            // Clear postcode when user types manually; we’ll refill after a valid selection
            addressField.addEventListener('input', () => {
                if (postalField) postalField.value = '';
            });

            const ac = new google.maps.places.Autocomplete(addressField, {
                types: ["address"],
                // If you want to restrict to a country, uncomment and set as needed:
                // componentRestrictions: { country: "gb" }
            });

            if (ac.setFields) {
                ac.setFields(["formatted_address", "address_components"]);
            }

            ac.addListener("place_changed", function() {
                const place = ac.getPlace();
                if (!place || !place.formatted_address) return;

                let formatted = place.formatted_address
                    .replace(/,\s*United Kingdom$/i, "")
                    .replace(/,\s*UK$/i, "");

                // Extract postal code
                const comps = place.address_components || [];
                const pcComp = comps.find(c => (c.types || []).includes("postal_code"));

                if (pcComp && postalField) {
                    const postcode = (pcComp.long_name || "").toUpperCase();
                    // Remove the postcode if appended in the address
                    const endPcRegex = new RegExp(`\\s*${postcode}\\s*$`, "i");
                    const midPcRegex = new RegExp(`,\\s*${postcode}(,|\\s|$)`, "i");
                    formatted = formatted.replace(endPcRegex, "").replace(midPcRegex, "$1").trim();
                    postalField.value = postcode;
                }

                addressField.value = formatted;
            });
        }
    </script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA7xLp13hLBGIDOt4BIJZrJF99ItTsya0g&libraries=places&callback=initAddressAutocomplete">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
   <script>
    $(function () {
        // your existing init
        if (!$.fn.select2) {
            console.error('Select2 failed to load');
            return;
        }
        $('.select2').select2({
            placeholder: 'Select',
            allowClear: true,
            width: '100%'
        });

        // helper to clear fields
        function clearFormFields() {
            $('#first_name').val('');
            $('#last_name').val('');
            $('#email').val('');
            $('#address').val('');
            $('#postal_code').val('');
            if (window.iti && typeof window.iti.setNumber === 'function') {
                window.iti.setNumber(''); // intl-tel-input
            } else {
                $('#phone').val('');
            }
            $('#phone').removeClass('is-invalid').data('phone-valid', ''); // reset validation flag
        }

        // when an option is selected
        $('#old_customer').on('select2:select', function (e) {
            const el = e.params.data.element;
            const $opt = $(el);

            const firstName  = $opt.data('first_name')  || '';
            const lastName   = $opt.data('last_name')   || '';
            const email      = $opt.data('email')       || '';
            const address    = $opt.data('address')     || '';
            const postalCode = $opt.data('postal_code') || '';
            const phone      = ($opt.data('phone') || '').toString().trim();

            // Fill simple fields
            $('#first_name').val(firstName);
            $('#last_name').val(lastName);
            $('#email').val(email);
            $('#address').val(address);
            $('#postal_code').val(postalCode);

            // Fill phone (respecting intl-tel-input if loaded)
            if (window.iti && typeof window.iti.setNumber === 'function' && phone) {
                // setNumber accepts E.164 like +441234567890; if your stored number
                // lacks +country code, iti will try to parse based on current country
                window.iti.setNumber(phone);
            } else {
                $('#phone').val(phone);
            }

            // remove any previous invalid state
            $('#phone').removeClass('is-invalid').data('phone-valid', '1');
        });

        // when cleared (allowClear: true)
        $('#old_customer').on('select2:clear', function () {
            clearFormFields();
        });

        // also handle manual change to the placeholder option
        $('#old_customer').on('change', function () {
            if (!$(this).val()) clearFormFields();
        });
    });
</script>

@endsection
