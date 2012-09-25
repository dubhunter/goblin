$(function (){
	$('.popModal').ajaxModal({
		autoCenter: true,
		closeOther: true,
		onOpen: function (dialog){
			modalEffect.fadeIn(dialog, function (){$.ajaxModal.center(dialog);xForm.init();});
		}
	});
});