$(function() {
   // Find your root SVG element
   var svg = document.getElementById('svg');
   
   // Create an SVGPoint for future math
   var pt = svg.createSVGPoint();
   
   // Get point in global SVG space
   function cursorPoint(evt){
     pt.x = evt.clientX; pt.y = evt.clientY;
     return pt.matrixTransform(svg.getScreenCTM().inverse());
   }
   
   function showCoords(evt) {
     var loc = cursorPoint(evt);
     $('#coord').html('(' + Math.round(loc.x) + ', ' + Math.round(loc.y) + ')');
     $('#coord').css('left',evt.pageX + 20);
     $('#coord').css('top',evt.pageY - 25);
   }
   
   svg.addEventListener('mousemove',showCoords,false);
   
   function showSpawnInfo(evt) {
      $('#spawninfo').css('left',evt.pageX + 20);
      $('#spawninfo').css('top',evt.pageY);
      $('#spawninfo').css('visibility','visible');
      $('#spawninfo').html(evt.target.getAttributeNS(null,'data-spawninfo'));
      var spawnXY = evt.target.getAttributeNS(null,'data-spawn');
   
      console.log(spawnXY);
   
      $('[data-grid='+spawnXY+']').css('visibility','visible');
   }
   
   function hideSpawnInfo(evt) {
      $('#spawninfo').css('visibility','hidden');
      $('[data-grid]').css('visibility','hidden');
   }
   
   var spawninfos = document.getElementsByClassName('spawninfos');
   var spawninfo = $('.spawninfo');
   
   spawninfo.on('mousemove',showSpawnInfo);
   spawninfo.on('mouseout',hideSpawnInfo);
});

