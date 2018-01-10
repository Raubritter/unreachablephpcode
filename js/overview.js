function selectprojects() {
	$.get("index.php?ajax=projects", function(data, status){
		var obj = JSON.parse(data);
		var posx = 0;
		var posy = 0;
		$.each(obj,function(key,value){
			$("#projects").append("<option>"+value+"</option>");
		});
	});
}
$(function () {
	selectprojects();
});