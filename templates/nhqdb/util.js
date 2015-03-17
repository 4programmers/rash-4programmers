userAgentLowerCase = navigator.userAgent.toLowerCase();

function resizeTextarea(t) {
    if ( !t.initialRows ) t.initialRows = t.rows;
    a = t.value.split('\n');
    b=0;
    for (x=0; x < a.length; x++) {
	if (a[x].length >= t.cols) b+= Math.floor(a[x].length / t.cols);
    }
    b += a.length;
    if (userAgentLowerCase.indexOf('opera') != -1) b += 2;
    if (b > t.rows || b < t.rows)
	t.rows = (b < t.initialRows ? t.initialRows : ((b < 50) ? b : 50));
}


function setFocus(id) {
    var e = document.getElementById(id);
    if (e) e.focus();
}


function mangle_quote(textareaid)
{
  var txtarea = document.getElementById(textareaid);
  if (!txtarea) return;

  var tsregexen = new Array(
	  /^\[?\d\d:?\d\d(:?\d\d)?\]? +/,
	  /^\[?(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) +[0123]\d +\d\d:?\d\d(:?\d\d)?\]? +/
  );

  var txt = txtarea.value;

  /* fix whitespace */
  txt = txt.replace(/\t/gm, " ");
  txt = txt.replace(/^\s+/, "");
  txt = txt.replace(/\s+$/, "");
  txt = txt.replace(/ +$/m, "");

  /* try to fix different timestamp styles */
  var lines = txt.split("\n");
  var style = -1;
  var oldstyle = -1;
  for (var i = 0; i < lines.length; i++) {
      style = -1;
      for (var j = 0; j < tsregexen.length; j++) {
	  if (lines[i].match(tsregexen[j])) {
	      style = j;
	      break;
	  }
      }

      if (style != -1) {
	  if ((oldstyle != -1) && (oldstyle != style)) {
	      style = -2;
	      break; /* different style lines, bail out */
	  } else {
	      oldstyle = style;
	  }
      }
  }

  if (style >= 0) {
      for (var i = 0; i < lines.length; i++) {
	  lines[i] = lines[i].replace(tsregexen[style],"");
      }
  }

  txt = lines.join("\n");

  txtarea.value = txt;
}