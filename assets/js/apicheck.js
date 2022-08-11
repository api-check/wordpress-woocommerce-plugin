jQuery(document).ready(function ($) {
    let selectedBillingPostalcodeId = null;
    let selectedBillingStreetId = null;

    let selectedShippingPostalcodeId = null;
    let selectedShippingStreetId = null;

    /*
      =========================
      Autocomplete configs
      =========================
     */
    const BillingMunicipalityAutocomplete = new autoComplete({
        selector: "#billing_municipality_autocomplete",
        placeHolder: "Start met typen...",
        cache: false,
        debounce: 500,
        searchEngine: "loose",
        data: {
            src: [],
            keys: ["name"],
        },
        resultsList: {
            position: "afterend",
            maxResults: 25,
            tabSelect: true,
            noResults: true,
        },
        resultItem: {
            highlight: true
        },
        events: {
            input: {
                selection: (event) => {
                    const selection = event.detail.selection.value;
                    BillingMunicipalityAutocomplete.input.value = selection.name;
                    $('#billing_municipality_city').val(selection.city);
                    $('#billing_municipality_postalcode').val(selection.postalcode);
                    selectedBillingPostalcodeId = selection.id;
                }
            }
        }
    });

    const BillingStreetAutocomplete = new autoComplete({
        selector: "#billing_street_autocomplete",
        placeHolder: "Start met typen...",
        cache: false,
        debounce: 500,
        searchEngine: "loose",
        data: {
            src: [],
            keys: ["name"],
        },
        resultsList: {
            position: "afterend",
            maxResults: 25,
            tabSelect: true,
            noResults: true,
        },
        resultItem: {
            highlight: true
        },
        events: {
            input: {
                selection: (event) => {
                    const selection = event.detail.selection.value;
                    BillingStreetAutocomplete.input.value = selection.name;
                    selectedBillingStreetId = selection.id;
                }
            }
        }
    });

    const ShippingMunicipalityAutocomplete = new autoComplete({
        selector: "#shipping_municipality_autocomplete",
        placeHolder: "Start met typen...",
        cache: false,
        debounce: 500,
        searchEngine: "loose",
        data: {
            src: [],
            keys: ["name"],
        },
        resultsList: {
            position: "afterend",
            maxResults: 25,
            tabSelect: true,
            noResults: true,
        },
        resultItem: {
            highlight: true
        },
        events: {
            input: {
                selection: (event) => {
                    const selection = event.detail.selection.value;
                    ShippingMunicipalityAutocomplete.input.value = selection.name;
                    $('#shipping_municipality_city').val(selection.city);
                    $('#shipping_municipality_postalcode').val(selection.postalcode);
                    selectedShippingPostalcodeId = selection.id;
                }
            }
        }
    });

    const ShippingStreetAutocomplete = new autoComplete({
        selector: "#shipping_street_autocomplete",
        placeHolder: "Start met typen...",
        cache: false,
        debounce: 500,
        searchEngine: "loose",
        data: {
            src: [],
            keys: ["name"],
        },
        resultsList: {
            position: "afterend",
            maxResults: 25,
            tabSelect: true,
            noResults: true,
        },
        resultItem: {
            highlight: true
        },
        events: {
            input: {
                selection: (event) => {
                    const selection = event.detail.selection.value;
                    ShippingStreetAutocomplete.input.value = selection.name;
                    selectedShippingStreetId = selection.id;
                }
            }
        }
    });

    const LookupHandler = function (fields) {
        this.prefix = fields.prefix;

        this.$companyField = $(fields.company + '_field');
        this.$countryField = $(fields.country + '_field');
        this.$stateField = $(fields.state + '_field');
        this.$cityField = $(fields.city + '_field');
        this.$address1Field = $(fields.address_1 + '_field');
        this.$address2Field = $(fields.address_2 + '_field');
        this.$streetField = $(fields.street + '_field');
        this.$postcodeField = $(fields.postcode + '_field');

        this.$housenumberField = $(fields.housenumber + '_field');
        this.$housenumberAdditionField = $(fields.housenumber_addition + '_field');
        this.$autocompleteMunicipalityField = $(fields.municipality_autocomplete + '_field');
        this.$autocompleteStreetField = $(fields.street_autocomplete + '_field');

        // Find and set fields
        this.$company = this.$companyField.find(':input');
        this.$country = this.$countryField.find(':input');
        this.$state = this.$stateField.find(':input');
        this.$city = this.$cityField.find(':input');
        this.$address1 = this.$address1Field.find(':input');
        this.$address2 = this.$address2Field.find(':input');
        this.$street = this.$streetField.find(':input');
        this.$postcode = this.$postcodeField.find(':input');

        this.$housenumber = this.$housenumberField.find(':input');
        this.$housenumberAddition = this.$housenumberAdditionField.find(':input');
        this.$autocompleteMunicipality = this.$autocompleteMunicipalityField.find(':input');
        this.$autocompleteStreet = this.$autocompleteStreetField.find(':input');

        if (!this.isCheckout()) {
            return;
        }

        this.$country.on('change', () => {
            this.setupFields(fields.prefix);
        });

        this.$autocompleteMunicipality.on('input', () => {
            this.municipalityAutocompleteChange(fields.prefix);
        });

        this.$autocompleteStreet.on('input', () => {
            this.streetAutocompleteChange(fields.prefix);
        });


        this.setupFields(fields.prefix);
    };

    LookupHandler.prototype.isCheckout = function () {
        return this.$country.length > 0 && this.$postcode.length > 0 && this.$city.length > 0;
    };

    LookupHandler.prototype.markFieldsAsRequired = function (fields) {
        const required = '<abbr class="required" title="">*</abbr>';

        fields.forEach(field => {
            field.find('label').children().remove();
            field.addClass('validated-required').find('label').append(required);
        });
    };

    LookupHandler.prototype.setupFields = function (prefix) {
        let selectedCountryCode = this.getSelectedCountryCode();
        this.reorderFields(selectedCountryCode);
        this.listen(selectedCountryCode);
        this.autoFillMyParcelFields(prefix);
    };

    LookupHandler.prototype.listen = function (selectedCountryCode) {
        if (this.isCountryEligibleForLookup(selectedCountryCode)) {
            this.applyFieldsLock();
        } else {
            this.$postcode.off('blur input');
            this.$housenumber.off('blur input');
            this.$housenumberAddition.off('blur input');
            this.hardResetFields();
            this.releaseFieldsLock();
        }
    };

    LookupHandler.prototype.reorderFields = function (selectedCountryCode) {
        if (this.isCountryEligibleForLookup(selectedCountryCode)) {
            this.hardResetFields();
            setTimeout(() => {
                if (selectedCountryCode === 'NL') {
                    // Set validators
                    this.markFieldsAsRequired([this.$streetField, this.$housenumberField]);
                    // Show fields
                    this.$streetField.show();
                    this.$cityField.show();
                    this.$postcodeField.show();
                    this.$housenumberField.show();
                    this.$housenumberAdditionField.show();
                    // Hide fields
                    this.$autocompleteMunicipalityField.hide();
                    this.$autocompleteStreetField.hide();

                    this.$cityField.insertAfter(this.$streetField);
                    this.$postcodeField.insertBefore(this.$housenumberField);
                } else if (selectedCountryCode === 'LU') {
                    // Set validators
                    this.markFieldsAsRequired([this.$streetField, this.$housenumberField]);
                    // Show fields

                    this.$streetField.show();
                    this.$cityField.show();
                    this.$postcodeField.show();
                    this.$housenumberField.show();
                    this.$housenumberAdditionField.show();
                    // Hide fields
                    this.$autocompleteMunicipalityField.hide();
                    this.$autocompleteStreetField.hide();

                    this.$cityField.insertAfter(this.$streetField);
                    this.$postcodeField.insertBefore(this.$housenumberField);
                } else if (selectedCountryCode === 'BE') {
                    // Set validators
                    this.markFieldsAsRequired([this.$autocompleteMunicipalityField, this.$autocompleteStreetField, this.$housenumberField]);
                    // Show fields
                    this.$autocompleteMunicipalityField.show();
                    this.$autocompleteMunicipality.val('');
                    this.$autocompleteStreetField.show();
                    this.$autocompleteStreet.val('');
                    // Hide fields
                    this.$postcodeField.hide();
                    this.$streetField.hide();
                    this.$cityField.hide();
                    this.$postcodeField.hide();
                    this.$address1Field.hide();
                    this.$address2Field.hide();

                    this.$autocompleteStreetField.insertAfter(this.$autocompleteMunicipalityField);
                }
            }, 1);
        } else {
            this.$streetField.hide();
            this.$streetNumberField.hide();
            this.$streetNumberSuffixField.hide();
            this.$address1Field.show();
            this.$address2Field.show();
            this.$autocompleteMunicipalityField.hide();
            this.$autocompleteStreetField.hide();
            this.$cityField.before(this.$postcodeField);
        }
    };

    LookupHandler.prototype.getSelectedCountryCode = function () {
        return this.$country.val().trim();
    };

    LookupHandler.prototype.autoFillMyParcelFields = function (prefix) {
        $(document.body).on('update_checkout', () => {
            $('#' + prefix + '_street_name_field').find(':input').val(this.$street.val());
            $('#' + prefix + '_house_number_field').find(':input').val(this.$housenumber.val());
            $('#' + prefix + '_house_number_suffix_field').find(':input').val(this.$housenumberAddition.val());
        })
    };

    LookupHandler.prototype.applyFieldsLock = function () {
        this.$postcode.attr('autocomplete', 'off');
        this.$postcode.attr('maxlength', 7);

        this.$street.attr('readonly', true);
        this.$city.attr('readonly', true);
        this.$state.attr('readonly', true);

        this.$stateField.addClass('spikkl-hidden');
    }

    LookupHandler.prototype.releaseFieldsLock = function () {
        this.$postcode.removeAttr('autocomplete');
        this.$postcode.removeAttr('maxlength');
        this.$street.removeAttr('readonly');
        this.$city.removeAttr('readonly');
        this.$state.removeAttr('readonly');
    };

    LookupHandler.prototype.softResetFields = function () {
        this.$street.val('');
        this.$city.val('');
        this.$state.val('').trigger('change');

        if (typeof this.$spinner !== 'undefined') {
            this.stopLoading()
        }
    };

    LookupHandler.prototype.hardResetFields = function () {
        this.$postcode.val('');
        this.$street.val('');
        this.$housenumber.val('');
        this.$housenumberAddition.val('');
        this.$city.val('');
        this.$autocompleteMunicipality.val('');
        this.$autocompleteStreet.val('');

        this.softResetFields();
    };

    LookupHandler.prototype.isCountryEligibleForLookup = function (selectedCountryCode) {
        selectedCountryCode = selectedCountryCode || this.getSelectedCountryCode();
        return spikkl_params.supported_countries.indexOf(selectedCountryCode) >= 0;
    };

    if (typeof spikkl_billing_fields !== 'undefined') {
        new LookupHandler(spikkl_billing_fields);
    }

    if (typeof spikkl_shipping_fields !== 'undefined' && $('#ship-to-different-address-checkbox').length) {
        new LookupHandler(spikkl_shipping_fields);
    }

    /*
      =========================
      Autocomplete field helpers
      =========================
     */
    // Type event on street textbox
    LookupHandler.prototype.streetAutocompleteChange = debounce(function (prefix) {
        var street = this.$autocompleteStreet.val();
        var params = {
            action: 'ac_search_address',
            country: this.getSelectedCountryCode(),
            postalcode_id: prefix === 'billing' ? selectedBillingPostalcodeId : selectedShippingPostalcodeId,
            street: street
        };
        // Show loader
        this.$autocompleteStreet.addClass('loadinggif');

        doApiCall(params, prefix).then((data => {
            result = data.result.data.Results;
            // Rebuild array for autocomplete
            parsedResults = [];
            result.forEach((item) => {
                parsedResults.push({
                    'name': parseBeLanguage(item.Street),
                    'id': item.Street.street_id,
                });
            });
            setDataForStreetAutocomplete(prefix, parsedResults)
            this.$autocompleteStreet.removeClass('loadinggif');
            this.applyFieldsLock();
        })).catch(() => {
            this.$autocompleteStreet.removeClass('loadinggif');
            this.releaseFieldsLock();
        });
    }, 250);

    // Type event on municipality textbox
    LookupHandler.prototype.municipalityAutocompleteChange = debounce(function (prefix) {
        var municipality = this.$autocompleteMunicipality.val();
        var params = {
            action: 'ac_search_address',
            country: this.getSelectedCountryCode(),
        };
        // Show loader
        this.$autocompleteMunicipality.addClass('loadinggif');

        // Check if query is numeric
        if (/^[0-9]+$/.test(municipality)) {
            // Query is numeric. Do postalcode lookup (postalcode returns only one result)
            params.postalcode = municipality;
            doApiCall(params, prefix).then((data => {
                result = data.result.data.Results;
                // Rebuild array for autocomplete
                parsedResults = [];
                result.forEach((item) => {
                    parsedResults.push({
                        'name': item.Postalcode.postalcode + " - " + parseBeLanguage(item.Municipality),
                        'id': item.Postalcode?.postalcode_id || item.postalcode_id,
                        'city': parseBeLanguage(item.Municipality),
                        'postalcode': item.Postalcode.postalcode,
                    });
                });
                setDataForMunicipalityAutocomplete(prefix, parsedResults)
                this.$autocompleteMunicipality.removeClass('loadinggif');
                this.applyFieldsLock();
            })).catch(() => {
                this.$autocompleteMunicipality.removeClass('loadinggif');
                this.releaseFieldsLock();
            });
        } else {
            // Look for municipality
            params.municipality = municipality;
            doApiCall(params, prefix).then((data => {
                result = data.result.data.Results;
                // Rebuild array for autocomplete
                parsedResults = [];
                result.forEach((item) => {
                    parsedResults.push({
                        'name': parseBeLanguage(item.Municipality) + " - " + item.Postalcode.postalcode,
                        'id': item.Postalcode.postalcode_id,
                        'city': parseBeLanguage(item.Municipality),
                        'postalcode': item.Postalcode.postalcode,
                    });
                });
                setDataForMunicipalityAutocomplete(prefix, parsedResults)
                this.$autocompleteMunicipality.removeClass('loadinggif');
                this.applyFieldsLock();
            })).catch(() => {
                this.$autocompleteMunicipality.removeClass('loadinggif');
                this.releaseFieldsLock();
            });
        }
    }, 250);

    function setDataForMunicipalityAutocomplete(prefix, data) {
        if (prefix == 'billing') {
            BillingMunicipalityAutocomplete.data.src = data;
            BillingMunicipalityAutocomplete.start();
        }

        if (prefix == 'shipping') {
            ShippingMunicipalityAutocomplete.data.src = data;
            ShippingMunicipalityAutocomplete.start();
        }
    }

    function setDataForStreetAutocomplete(prefix, data) {
        if (prefix == 'billing') {
            BillingStreetAutocomplete.data.src = data;
            BillingStreetAutocomplete.start();
        }

        if (prefix == 'shipping') {
            ShippingStreetAutocomplete.data.src = data;
            ShippingStreetAutocomplete.start();
        }
    }

    /*
      =========================
      Generic fields
      =========================
     */
    for (let type of ['billing', 'shipping']) {
        jQuery(document).on("blur change", `#${type}_postcode, #${type}_housenumber, #${type}_housenumber_addition`, function () {
            FieldChanged(type);
        });
    }

    let prevSearchAddressValues = {billing:'',shipping:''};
    function FieldChanged(type) {
        var country = jQuery('#' + type + "_country option:selected").val().trim().toUpperCase();
        var postcode = jQuery('#' + type + "_postcode").val().trim() || jQuery('#' + type + '_municipality_postalcode').val().trim();
        var housenumber = jQuery('#' + type + "_housenumber").val().trim();
        var housenumberAddition = jQuery('#' + type + "_housenumber_addition").val().trim();

        if (country && housenumber && postcode) {
            let hash = country + '/' + housenumber + '/' + housenumberAddition + '/' + postcode;
            if (prevSearchAddressValues[type] === hash) {
                return; // Prevent double searches
            }
            prevSearchAddressValues[type] = hash;



            let params = {
                'action': 'ac_search_address',
                'country': country,
                'number': housenumber,
            };

            if (country === 'BE') {
                /*be?postalcode_id=908&street_id=96511&number=1&boxNumber=*/
                params.postalcode_id = type === 'billing' ? selectedBillingPostalcodeId : selectedShippingPostalcodeId;
                params.street_id = type === 'billing' ? selectedBillingStreetId : selectedBillingStreetId;
                if (housenumberAddition) {
                    params.boxNumber = housenumberAddition;
                }
            } else {
                params.postalcode = postcode;
                if (housenumberAddition) {
                    params.numberAddition = housenumberAddition;
                }
            }


            // Show loader
            jQuery('#' + type + '_street').css("opacity", "0.5").addClass('loadinggif');
            jQuery('#' + type + '_city').css("opacity", "0.5").addClass('loadinggif');

            doApiCall(params, type).then((data => {
                // Hide loader
                jQuery('#' + type + '_street')
                    .css("opacity", 1)
                    .removeClass('loadinggif')
                    .val(data.result.data.street);
                jQuery('#' + type + '_city')
                    .css("opacity", 1)
                    .removeClass('loadinggif')
                    .val(data.result.data.city);
            }), error => {
                // Hide loader
                jQuery('#' + type + '_street')
                    .css("opacity", 1)
                    .removeClass('loadinggif')
                    .val('')
                    .removeAttr('readonly');
                jQuery('#' + type + '_city')
                    .css("opacity", 1)
                    .removeClass('loadinggif')
                    .val('')
                    .removeAttr('readonly');
            });
        }
    }

    function parseBeLanguage(value) {
        if (value.nameNL && value.nameFR) {
            return (navigator.language || navigator.userLanguage || 'nl').toLowerCase() === 'fr'
                ? value.nameFR
                : value.nameNL;
        } else {
            return value.nameNL || value.nameFR || '';
        }
    }

    function debounce(func, wait, immediate) {
        var timeout;
        return function () {
            var context = this, args = arguments;
            var later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    function doApiCall(data, type) {
        type = type.replace('#', '');
        console.trace('doing api call', data);
        return new Promise((resolve, reject) => {
            try {
                jQuery.post(ac_ajax_object.ajax_url, data, function (response) {
                    console.log(response);
                    if (response.status == 1) {
                        jQuery(`.woocommerce-${type}-fields .acMessage`).remove();
                        resolve(response);
                    } else {
                        // Something went wrong
                        if (!jQuery(`.woocommerce-${type}-fields .acMessage`).length > 0) {
                            jQuery(`#${type}_country_field`).after("<div class='acMessage'>" + response.result + "</div>");
                        }
                        reject();
                    }
                });
            } catch (e) {
                console.error('err below');
                console.error(e);
                reject(e);
            }
        });
    }
});