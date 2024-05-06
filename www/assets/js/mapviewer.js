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

   var activeGrids = [];
   
   function showCoords(evt) {
     var loc = cursorPoint(evt);
     $('#coord').html('(y:' + -Math.round(loc.y) + ', x:' + -Math.round(loc.x) + ')');
     $('#coord').css('left',evt.pageX + 20);
     $('#coord').css('top',evt.pageY - 25);
   }
   
   svg.addEventListener('mousemove',showCoords,false);
   
   function showSpawnInfo(evt) {
      $('#spawninfo').css('left',evt.pageX + 20);
      $('#spawninfo').css('top',evt.pageY);
      $('#spawninfo').css('visibility','visible');

      var spawnInfoEncoded = evt.target.getAttributeNS(null,'data-spawninfo');
      var spawnXY          = evt.target.getAttributeNS(null,'data-spawn');
      var spawnInfo        = $.parseJSON(atob(spawnInfoEncoded));

      var spawnHtml = '<table border=0><tr>';

      for (var groupEntryKey in spawnInfo) {
         var groupEntry = spawnInfo[groupEntryKey];
         spawnHtml = spawnHtml.concat("<td class='align-text-top'><b>"+groupEntry['groupName']+" ("+groupEntry['groupId']+")</b>"+
                                      "<pre>"+groupEntry['groupSpawnList'].join("\n")+"</pre>"+"<span class='ml-4'><i>"+groupEntry['roamType']+
                                      "</i></span></td>"); 
      };

      spawnHtml = spawnHtml.concat('</tr></table>');

      $('#spawninfo').html(spawnHtml);
   
      showSpawnGrid(evt);   
   }  
   
   function hideSpawnInfo(evt) {
      $('#spawninfo').css('visibility','hidden');
      hideSpawnGrid(evt); 
   }

   function showSpawnGrid(evt) {
      var spawnXY = evt.target.getAttributeNS(null,'data-spawn');
      var pathing = evt.target.getAttributeNS(null,'data-pathing');

      //console.log(spawnXY,pathing);

      if (!pathing) { return; }
      if (activeGrids[pathing]) { return; }

      evt.target.classList.add('spawninfoClicked');
      
      $('[data-grid='+pathing+']').css('visibility','visible');
   }

   function hideSpawnGrid(evt) {
      var spawnXY = evt.target.getAttributeNS(null,'data-spawn');
      var pathing = evt.target.getAttributeNS(null,'data-pathing');

      if (!pathing) { return; }
      if (activeGrids[pathing]) { return; }

      evt.target.classList.remove('spawninfoClicked');

      $('[data-grid='+pathing+']').css('visibility','hidden');
   }

   function activateSpawnGrid(evt) {
      var spawnXY  = evt.target.getAttributeNS(null,'data-spawn');
      var pathing  = evt.target.getAttributeNS(null,'data-pathing');
      var newState = activeGrids[pathing] ? false : true;
      activeGrids[pathing] = newState;
   }
   
   var spawninfos = document.getElementsByClassName('spawninfos');
   var spawninfo = $('.spawninfo');
   
   spawninfo.on('mouseup',activateSpawnGrid);
   spawninfo.on('mouseover',showSpawnInfo);
   spawninfo.on('mouseout',hideSpawnInfo);
});

