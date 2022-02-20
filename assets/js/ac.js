jQuery(document).ready(function(){


	jQuery(document).on("blur","#billing_postcode", function(){
		
		var country = jQuery("#billing_country option:selected").val();
		
		if(country == undefined){
			country = jQuery("#billing_country").val()
		}else{
			country = jQuery("#billing_country option:selected").val();
		}
		var address = jQuery("#house_no").val();
		var postcode = jQuery("#billing_postcode").val();

		jQuery("p#billing_postcode_field #billing_postcode").val(jQuery(this).val());

		if(country != "" && address != "" && postcode != ""){

			
			jQuery("#billing_address_1").css("opacity","0.5");
			jQuery("#billing_address_1").addClass('loadinggif');

			jQuery("#billing_city").css("opacity","0.5");
			jQuery("#billing_city").addClass('loadinggif');


			var data = {
				'action': 'ac_search_address',
				'country': country,
				'address':address,
				'postcode':postcode,

			};
			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post(ac_ajax_object.ajax_url, data, function(response) {

				if(response.status == 1){
					var street = response.result.data.street;
					var city = response.result.data.city;

					jQuery("#billing_address_1").val(street);
					jQuery("#billing_city").val(city);

					jQuery("#billing_address_1").css("opacity",1);
					jQuery("#billing_address_1").removeClass('loadinggif');

					jQuery("#billing_city").css("opacity",1);
					jQuery("#billing_city").removeClass('loadinggif');

					jQuery(".acMessage").remove();


				}else{
					if(!jQuery('.acMessage').length > 0) { 
    					jQuery("#billing_country_field").after("<div class='acMessage'>"+response.result+"</div>");
					}

					jQuery("#billing_address_1").css("opacity",1);
					jQuery("#billing_address_1").removeClass('loadinggif');

					jQuery("#billing_city").css("opacity",1);
					jQuery("#billing_city").removeClass('loadinggif');

					jQuery("#billing_address_1").val("");
					jQuery("#billing_city").val("");

				}


			});
		}
	});


	jQuery(document).on("blur","#house_no", function(){


		jQuery(".col-2 #house_no").val(jQuery(this).val());
		
		var country = jQuery("#billing_country option:selected").val();
		
		if(country == undefined){
			country = jQuery("#billing_country").val()
		}else{
			country = jQuery("#billing_country option:selected").val();
		}
			var address = jQuery("#house_no").val();
			var postcode = jQuery("#billing_postcode").val();

		if(country != "" && address != "" && postcode != ""){

			

			jQuery("#billing_address_1").css("opacity","0.5");
			jQuery("#billing_address_1").addClass('loadinggif');

			jQuery("#billing_city").css("opacity","0.5");
			jQuery("#billing_city").addClass('loadinggif');



			var data = {
				'action': 'ac_search_address',
				'country': country,
				'address':address,
				'postcode':postcode,

			};
			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post(ac_ajax_object.ajax_url, data, function(response) {

				if(response.status == 1){
					
					//var number = response.result.data.number;
					var street = response.result.data.street;
					var city = response.result.data.city;

					jQuery("#billing_address_1").val(street);
					jQuery("#billing_city").val(city);
					
					jQuery("#billing_address_1").css("opacity",1);
					jQuery("#billing_address_1").removeClass('loadinggif');

					jQuery("#billing_city").css("opacity",1);
					jQuery("#billing_city").removeClass('loadinggif');

					jQuery(".acMessage").remove();

				}else{
					//alert(response.result);

					if(!jQuery('.acMessage').length > 0) { 
    					jQuery("#billing_country_field").after("<div class='acMessage'>"+response.result+"</div>");
					}

			
					jQuery("#billing_address_1").css("opacity",1);
					jQuery("#billing_address_1").removeClass('loadinggif');

					jQuery("#billing_city").css("opacity",1);
					jQuery("#billing_city").removeClass('loadinggif');

					jQuery("#billing_address_1").val("");
					jQuery("#billing_city").val("");

				}


			});
		}
	});


	var html = jQuery("#billing_postcode_field").html();
	jQuery("#billing_country_field").append("<div class='LeftSide'>"+html+"</div>");

	var html = jQuery(".houseNo").html();
	jQuery("#billing_country_field").append("<div class='MiddleSide'>"+html+"</div>");


	var html3 = jQuery("#billing_address_2_field").html();
	jQuery("#billing_country_field").append("<div class='RightSide'><label for='billing_postcode'> Apartment/Suite </label>"+html3+"</div>");


	jQuery("#billing_country").on("change", function(){
		//alert("HELLO WORLD");
		jQuery("#house_no").val("");
		jQuery("#billing_postcode").val("");
	});


	jQuery(document).on("blur","#billing_address_2", function(){
		jQuery("#billing_address_2_field #billing_address_2").val(jQuery(this).val());
	});


	


});