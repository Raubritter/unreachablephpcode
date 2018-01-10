	function baseName(str)
	{
	   var base = new String(str).substring(str.lastIndexOf('/') + 1); 
	   return base;
	}
	function makeclass(key,name) {
		
			var text = new fabric.Text(key, {
				textAlign: 'right',
				fontSize: 15,
				left: -75,
				top: -40
			});
			var file = new fabric.Rect({
				left: 0,
				top: 0,
				width: 200,
				height: 100,
				fill:"white",
				stroke: 'black',
				strokeWidth: 1
			  });
			var functiontext = new fabric.Text(name, {
			  textAlign: 'left',
			  fontSize: 15,
			  left: -55,
			  top: 20
			});
			var line = new fabric.Line([0, 20, 200, 20],{
				left: 0,
				top: -25,
				originY: 'top'
			});
			var group = new fabric.Group([line,text,functiontext,file ], {
			  draggable:false,
			  subTargetCheck: true
			});
			canvas.on('mouse:down', function (e) {
			if (e.target !== "undefined") {
				$.get("index.php?area=code&ajax=code&file="+getParameterByName("file"), function(data, status){
					var editor = ace.edit("editor");
					editor.setTheme("ace/theme/chrome");
					editor.getSession().setMode("ace/mode/php");
					editor.setReadOnly(true);
					editor.setValue(data);
					
					$.get("index.php?area=code&ajax=problem&file="+getParameterByName("file"), function(data, status){
						data = JSON.parse(data);
						var Range = ace.require('ace/range').Range;
						$.each(data,function(key,value){
								$.each(value,function(key2,value2){
									editor.session.addMarker(new Range(value2.from-1, 0, value2.to-1, 1), "myMarker", "fullLine");
								});
						});
					});
					$("#editor").show();
					$("#c").hide();
				});
			}
		});
		return group;
	}
	function makeArrow(coords) {
		var line,
        arrow,
        circle;

		line = new fabric.Line(coords, {
			stroke: '#000'
		});
        var centerX = (line.x1 + line.x2) / 2,
            centerY = (line.y1 + line.y2) / 2,
			deltaX = line.left - centerX,
			deltaY = line.top - centerY,
			angle = calcArrowAngle(line.x1, line.y1, line.x2, line.y2);
        arrow = new fabric.Triangle({
            left: line.get('x2') + deltaX,
            top: line.get('y2') + deltaY,
            originX: 'center',
            originY: 'center',
            hasBorders: false,
            hasControls: false,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            pointType: 'arrow_start',
            angle: angle+90,
            width: 10,
            height: 10,
            fill: '#000',
			evented:false
        });
        //arrow.line = line;

        circle = new fabric.Circle({
            left: line.get('x1') + deltaX,
            top: line.get('y1') + deltaY,
            radius: 2,
            stroke: '#000',
            strokeWidth: 3,
            originX: 'center',
            originY: 'center',
            hasBorders: false,
            hasControls: false,
            lockScalingX: true,
            lockScalingY: true,
            lockRotation: true,
            pointType: 'arrow_end',
            fill: '#000',
			evented:false
        });
        //circle.line = line;

        line.customType = arrow.customType = circle.customType = 'arrow';
        line.circle = arrow.circle = circle;
        line.arrow = circle.arrow = arrow;
		var group = new fabric.Group([ line, arrow, circle ], {
		  draggable:false
		});
		canvas.add(group);
		
		function moveEnd(obj) {
            var p = obj,
                x1, y1, x2, y2;

            if (obj.pointType === 'arrow_end') {
                obj.line.set('x2', obj.get('left'));
                obj.line.set('y2', obj.get('top'));
            } else {
                obj.line.set('x1', obj.get('left'));
                obj.line.set('y1', obj.get('top'));
            }

            obj.line._setWidthHeight();

            x1 = obj.line.get('x1');
            y1 = obj.line.get('y1');
            x2 = obj.line.get('x2');
            y2 = obj.line.get('y2');

            angle = calcArrowAngle(x1, y1, x2, y2);
            if (obj.pointType === 'arrow_end') {
                obj.arrow.set('angle', angle - 90);
            } else {
                obj.set('angle', angle - 90);
            }

            obj.line.setCoords();
            canvas.renderAll();
        }

        function moveLine() {
            var oldCenterX = (line.x1 + line.x2) / 2,
                oldCenterY = (line.y1 + line.y2) / 2,
                deltaX = line.left - oldCenterX,
                deltaY = line.top - oldCenterY;

            line.arrow.set({
                'left': line.x1 + deltaX,
                'top': line.y1 + deltaY
            }).setCoords();

            line.circle.set({
                'left': line.x2 + deltaX,
                'top': line.y2 + deltaY
            }).setCoords();

            line.set({
                'x1': line.x1 + deltaX,
                'y1': line.y1 + deltaY,
                'x2': line.x2 + deltaX,
                'y2': line.y2 + deltaY
            });

            line.set({
                'left': (line.x1 + line.x2) / 2,
                'top': (line.y1 + line.y2) / 2
            });
        }
		return line;
	}
	function calcArrowAngle(x1, y1, x2, y2) {
        var angle = 0,
            x, y;

        x = (x2 - x1);
        y = (y2 - y1);

        if (x === 0) {
            angle = (y === 0) ? 0 : (y > 0) ? Math.PI / 2 : Math.PI * 3 / 2;
        } else if (y === 0) {
            angle = (x > 0) ? 0 : Math.PI;
        } else {
            angle = (x < 0) ? Math.atan(y / x) + Math.PI : (y < 0) ? Math.atan(y / x) + (2 * Math.PI) : Math.atan(y / x);
        }

        return (angle * 180 / Math.PI);
    }
	function traverse(o,func,posx,posy,oldfile) {
		var starty = 0;
		for (var i in o) {
			var newfile = func.apply(this,[i,posx,posy+starty*100,null]);
			if(oldfile !== null){
				makeArrow([ oldfile.left+100, oldfile.top,newfile.left-100, newfile.top ]);
			}
			starty++;
			if (o[i] !== null && typeof(o[i])=="object") {
				traverse(o[i],func,posx+320,posy,newfile);
			}
		}
	}
	function createfilegroup(key,posx,posy,line) {
		var group = makefile(key,posx,posy,line);
		canvas.add(group);
		return group;
	}
	function createmainclass(obj) {
		$.each(obj[0],function(key,value){
			var functions = "";
			$.each(obj[1][key],function(key,value){
				if(value.visibility === 1){
					functions += "+ ";
				}
				if(value.visibility === 2){
					functions += "# ";
				}
				if(value.visibility === 4){
					functions += "- ";
				}
				functions += value.name+"()\n";
			});
			var mainclass = makeclass(key,functions);

			canvas.add(mainclass);
			canvas.centerObject(mainclass);
			mainclass.setCoords();
		});
	}
	function changeproj(){
		canvas.clear();
		displayoverview($("#upload option:selected").text());
	}
	function displayoverview(proj){
		if(proj !== undefined){
			proj = "&proj="+proj;
		} else {
			proj = "";
		}
		$.get("index.php?ajax=class&file="+getParameterByName("file")+proj, function(data, status){
			var obj = JSON.parse(data);
			var posx = 0;
			var posy = 0;
			createmainclass(obj);
			//traverse(obj,createfilegroup,posx,posy,null);
		});
	}
	function selectprojects() {
		$.get("index.php?ajax=projects", function(data, status){
			var obj = JSON.parse(data);
			var posx = 0;
			var posy = 0;
			$.each(obj,function(key,value){
				$("#projects").append("<option value="+value+">"+value+"</option>");
			});
			var proj = getParameterByName("proj");
			$("#projects").val(proj);
		});
	}
	function getParameterByName(name, url) {
		if (!url) url = window.location.href;
		name = name.replace(/[\[\]]/g, "\\$&");
		var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
			results = regex.exec(url);
		if (!results) return null;
		if (!results[2]) return '';
		return decodeURIComponent(results[2].replace(/\+/g, " "));
	}
	$(function () {
		canvas = new fabric.Canvas('c');
		displayoverview(getParameterByName("proj"));
		selectprojects();
	});