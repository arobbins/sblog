jQuery(function()
{
	jQuery('input[type="checkbox"]:checked, input[type="radio"]:checked').addClass('checked');
	
	jQuery('.sky-form').on('change', 'input[type="radio"]', function()
	{
		jQuery(this).closest('.sky-form').find('input[name="' + jQuery(this).attr('name') + '"]').removeClass('checked');
		jQuery(this).addClass('checked');
	});
	
	jQuery('.sky-form').on('change', 'input[type="checkbox"]', function()
	{
		jQuery(this).toggleClass('checked');
	});
});