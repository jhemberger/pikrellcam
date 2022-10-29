var move_mode = "move_coarse";

function move_region_mode($cb) {
	if ($cb.checked)
		move_mode = "move_coarse";
	else
		move_mode = "move_fine";
}

function move_region(where) {
//	alert("motion " + move_mode +  " " + where);
	fifo_command("motion " +  move_mode +  " " + where);
}

function tl_start() {
	var period;

	period = document.getElementById('tl_period').value;
	fifo_command("tl_start " +  period);
}


function image_expand_toggle() {
	var mjpeg_img = document.getElementById("mjpeg_image");
	mjpeg_img.classList.toggle("expanded-image");
}

function new_region() {
	fifo_command("motion new_region 0.3 0.3 0.3 0.3");
//	alert("Two consecutive fifo_command() not working.");
//	fifo_command("motion select_region last\n");
}

function list_regions() {
	fifo_command("motion list_regions");
}

function load_regions() {
	fifo_command('motion load_regions_show ' + document.getElementById('load_regions').value);
	document.getElementById('load_regions').value = "";
}

function save_regions() {
	fifo_command('motion save_regions ' + document.getElementById('save_regions').value);
	document.getElementById('save_regions').value = "";
}


var mjpeg;

function mjpeg_read() {
	setTimeout("mjpeg.src = 'mjpeg_read.php?time=' + new Date().getTime();", 150);
}

function mjpeg_start() {
	mjpeg = document.getElementById("mjpeg_image");
	mjpeg.onload = mjpeg_read;
	mjpeg.onerror = mjpeg_read;
	mjpeg_read();
}


function audio_play() {
	var audio_file = document.getElementById("audio_fifo");
	audio_file.src = document.getElementById("audio_fifo").src;
	audio_file.play();
	fifo_command("audio stream_open");
}

function audio_stop() {
	var audio_file = document.getElementById("audio_fifo");
	fifo_command("audio stream_close");
	audio_fifo.pause();
	audio_fifo.currentTime = 0;
}

function create_XMLHttpRequest() {
	if (window.XMLHttpRequest)
		return new XMLHttpRequest();
	else
		return new ActiveXObject("Microsoft.XMLHTTP");	// IE6, IE5
}

var sys_cmd = create_XMLHttpRequest();

function pikrellcam(start_stop) {
	sys_cmd.open("PUT", "sys_command.php?cmd=pikrellcam_" + start_stop, true);
	sys_cmd.send();
}

var fifo_cmd = create_XMLHttpRequest();

function fifo_command (cmd) {
	fifo_cmd.open("PUT", "fifo_command.php?cmd=" + cmd, true);
	fifo_cmd.send();
}



// servos
var servo_mode = 0;

var servo_left_array = [
    "images/arrow0-left.png",
    "images/arrow-left.png",
    "images/arrow2-left.png"
    ];

var servo_right_array = [
    "images/arrow0-right.png",
    "images/arrow-right.png",
    "images/arrow2-right.png"
    ];

var servo_up_array = [
    "images/arrow0-up.png",
    "images/arrow-up.png",
    "images/arrow2-up.png"
    ];

var servo_down_array = [
    "images/arrow0-down.png",
    "images/arrow-down.png",
    "images/arrow2-down.png"
    ];

function servo_move_mode() {
    servo_mode += 1;
    if (servo_mode > servo_left_array.length - 1) {
        servo_mode = 0;
    }
    document.getElementById("servo_left").src = servo_left_array[servo_mode];
    document.getElementById("servo_right").src = servo_right_array[servo_mode];
    document.getElementById("servo_up").src = servo_up_array[servo_mode];
    document.getElementById("servo_down").src = servo_down_array[servo_mode];
}

function servo_move_command(pan_tilt) {
//  alert("motion " + move_mode +  " " + where);
    fifo_command("servo " +  pan_tilt +  " " + servo_mode);
}

