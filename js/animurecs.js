// window scroll event throttling.
var scrollThrottleDelay = 100;
var scrollThrottleTimer = null;

var interval = {
  //to keep a reference to all the intervals
  intervals : {},

  //create another interval
  make : function ( fun, delay, id ) {
    //see explanation after the code
    var newInterval = setInterval.apply(
      window,
      [ fun, delay ].concat( [].slice.call(arguments, 2) )
    );
    this.intervals[ id ] = newInterval;
    return newInterval;
  },

  //clear a single interval
  clear : function ( id ) {
    return clearInterval( this.intervals[id] );
  },

  //clear all intervals
  clearAll : function () {
    for (var key in this.intervals) {
      clearInterval(this.intervals[key]);
    }
  }
};

/* Default class modification */
$.extend( $.fn.dataTableExt.oStdClasses, {
  "sWrapper": "dataTables_wrapper form-inline"
} );

/* API method to get paging information */
$.fn.dataTableExt.oApi.fnPagingInfo = function ( oSettings ) {
  return {
    "iStart":         oSettings._iDisplayStart,
    "iEnd":           oSettings.fnDisplayEnd(),
    "iLength":        oSettings._iDisplayLength,
    "iTotal":         oSettings.fnRecordsTotal(),
    "iFilteredTotal": oSettings.fnRecordsDisplay(),
    "iPage":          Math.ceil( oSettings._iDisplayStart / oSettings._iDisplayLength ),
    "iTotalPages":    Math.ceil( oSettings.fnRecordsDisplay() / oSettings._iDisplayLength )
  };
}

/* Bootstrap style pagination control */
$.extend( $.fn.dataTableExt.oPagination, {
  "bootstrap": {
    "fnInit": function( oSettings, nPaging, fnDraw ) {
      var oLang = oSettings.oLanguage.oPaginate;
      var fnClickHandler = function ( e ) {
        e.preventDefault();
        if ( oSettings.oApi._fnPageChange(oSettings, e.data.action) ) {
          fnDraw( oSettings );
        }
      };

      $(nPaging).addClass('pagination').append(
        '<ul>'+
          '<li class="prev disabled"><a href="#">&larr; '+oLang.sPrevious+'</a></li>'+
          '<li class="next disabled"><a href="#">'+oLang.sNext+' &rarr; </a></li>'+
        '</ul>'
      );
      var els = $('a', nPaging);
      $(els[0]).on( 'click.DT', { action: "previous" }, fnClickHandler );
      $(els[1]).on( 'click.DT', { action: "next" }, fnClickHandler );
    },

    "fnUpdate": function ( oSettings, fnDraw ) {
      var iListLength = 5;
      var oPaging = oSettings.oInstance.fnPagingInfo();
      var an = oSettings.aanFeatures.p;
      var i, j, sClass, iStart, iEnd, iHalf=Math.floor(iListLength/2);

      if ( oPaging.iTotalPages < iListLength) {
        iStart = 1;
        iEnd = oPaging.iTotalPages;
      }
      else if ( oPaging.iPage <= iHalf ) {
        iStart = 1;
        iEnd = iListLength;
      } else if ( oPaging.iPage >= (oPaging.iTotalPages-iHalf) ) {
        iStart = oPaging.iTotalPages - iListLength + 1;
        iEnd = oPaging.iTotalPages;
      } else {
        iStart = oPaging.iPage - iHalf + 1;
        iEnd = iStart + iListLength - 1;
      }

      for ( i=0, iLen=an.length ; i<iLen ; i++ ) {
        // Remove the middle elements
        $('li:gt(0)', an[i]).filter(':not(:last)').remove();

        // Add the new list items and their event handlers
        for ( j=iStart ; j<=iEnd ; j++ ) {
          sClass = (j==oPaging.iPage+1) ? 'class="active"' : '';
          $('<li '+sClass+'><a href="#">'+j+'</a></li>')
            .insertBefore( $('li:last', an[i])[0] )
            .on('click', function (e) {
              e.preventDefault();
              oSettings._iDisplayStart = (parseInt($('a', this).text(),10)-1) * oPaging.iLength;
              fnDraw( oSettings );
            } );
        }

        // Add / remove disabled classes from the static elements
        if ( oPaging.iPage === 0 ) {
          $('li:first', an[i]).addClass('disabled');
        } else {
          $('li:first', an[i]).removeClass('disabled');
        }

        if ( oPaging.iPage === oPaging.iTotalPages-1 || oPaging.iTotalPages === 0 ) {
          $('li:last', an[i]).addClass('disabled');
        } else {
          $('li:last', an[i]).removeClass('disabled');
        }
      }
    }
  }
} );

$.fn.hasAttr = function(name) {  
   return this.attr(name) !== undefined;
};

$.fn.disable = function() {
    return this.each(function() {
        if (typeof this.disabled != "undefined") this.disabled = true;
    });
};

$.fn.enable = function() {
    return this.each(function() {
        if (typeof this.disabled != "undefined") this.disabled = false;
    });
};

Array.max = function( array ){
    return Math.max.apply( Math, array );
};
Array.min = function( array ){
   return Math.min.apply( Math, array );
};

function initDataTable(elt) {
  // see if there's a default-sort column. if not, default to the first column.
  defaultSortColumn = $(elt).find('thead > tr > th').index($(elt).find('thead > tr > th.dataTable-default-sort'));
  if (defaultSortColumn == -1) {
    defaultSortColumn = 0;
    defaultSortOrder = "asc";
  } else {
    defaultSortOrder = $(elt).find('thead > tr > th.dataTable-default-sort').hasAttr("data-sort-order") ? $(elt).find('thead > tr > th.dataTable-default-sort').attr("data-sort-order") : "asc";
  }
  secondSortColumn = $(elt).find('thead > tr > th').index($(elt).find('thead > tr > th.dataTable-secondary-sort'));
  if (secondSortColumn == -1) {
    secondSortColumn = defaultSortColumn > 0 ? 0 : 1;
    secondSortOrder = "asc";
  } else {
    secondSortOrder = $(elt).find('thead > tr > th.dataTable-secondary-sort').hasAttr("data-sort-order") ? $(elt).find('thead > tr > th.dataTable-secondary-sort').attr("data-sort-order") : "asc";
  }
  recordsPerPage = $(elt).hasAttr('data-recordsPerPage') ? $(elt).attr('data-recordsPerPage') : 25;
  $(elt).dataTable({
    "sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span6'i><'span6'p>>",
    "sPaginationType": "bootstrap",
    "oLanguage": {
      "sLengthMenu": "_MENU_ records per page"
    },
    "iDisplayLength": recordsPerPage,
    "bPaginate": false,
    "bFilter": false,
    "bInfo": false,
    "aaSorting": [[defaultSortColumn, defaultSortOrder], [secondSortColumn, secondSortOrder]]
  });
}

function displayListEditForm(elt) {
  // converts a normal table list entry into a list edit form.
  // is called on the last cell of a list entry row, on the icon's link.

  $(elt).parent().parent().find('.listEntryTitle .listEntryStatus').removeClass('hidden');
  scoreNode = $(elt).parent().parent().find('.listEntryScore');
  score = $(scoreNode).html();
  if (score == "") {
    score = 0;
  } else {
    score = parseFloat(score.split("/")[0]).toFixed(2);
  }
  $(scoreNode).html("<div class='input-append'><input class='form-control input-sm' name='anime_entries[score]' type='number' min=0 max=10 step='any' value='"+score+"' /><span class='add-on'>/10</span></div>");

  episodeNode = $(elt).parent().parent().find('.listEntryEpisode');
  episodeText = $(episodeNode).html();
  if (episodeText == "") {
    episodes = 0;
    episodeTotal = 0;
  } else {
    episodes = parseInt(episodeText.split("/")[0]);
    episodeTotal = parseInt(episodeText.split("/")[1]);
  }
  $(episodeNode).html("<div class='input-append'><input class='form-control input-sm' name='anime_entries[episode]' type='number' min=0 step=1 value='"+episodes+"' /><span class='add-on'>/" + episodeTotal + "</span></div>");

  editNode = $(elt).parent();
  url = $(elt).attr('data-url');
  $(editNode).empty().append($('<a></a>').attr('href', '#').attr('data-url', url).addClass('btn btn-mini btn-primary').text("Update").click(function(event) {
    submitListUpdate(this);
    event.preventDefault();
  }));
}

function revertListEditForm(elt) {
  // converts a list edit form back to a normal table list entry.
  // is called on the last cell of a list entry row, on the button.
  var url = $(elt).attr('data-url');
  var rowNode = $(elt).parent().parent();
  var buttonNode = $(elt).parent();

  var statusNode = $(rowNode).find('.listEntryTitle .listEntryStatus');
  statusNode.addClass('hidden');

  var scoreNode = $(elt).parent().parent().find('.listEntryScore');
  var scoreVal = scoreNode.find('input').val();
  $(scoreNode).html(scoreVal == "0" ? "" : scoreVal + "/10");

  var episodeNode = $(elt).parent().parent().find('.listEntryEpisode');
  var finishedEps = $(episodeNode).find('.input-append input').val();
  var totalEps = $(episodeNode).find('.input-append .add-on').text();
  $(episodeNode).html(finishedEps + totalEps);

  $(buttonNode).empty().append($('<a></a>').attr('href', '#').attr('data-url', url).addClass('listEdit').append("<i></i>").addClass('icon-pencil').click(function(event) {
    displayListEditForm(this);
    event.preventDefault();
  }));
}

function submitListUpdate(elt) {
  // submits a list entry update.
  // is called on the last cell of a list entry row, on the button.

  tableNode = $(elt).parent().parent().parent().parent();
  rowNode = $(elt).parent().parent();

  $(elt).off('click');
  $(elt).addClass("disabled");
  $(elt).text("Updating...");

  user_id = parseInt($(tableNode).attr('data-id'));
  anime_id = parseInt($(rowNode).attr('data-id'));
  status = parseInt($(rowNode).find('.listEntryStatus').find('option:selected').val());
  score = parseFloat($(rowNode).find('.listEntryScore').find('input').val()).toFixed(2);
  episode = parseInt($(rowNode).find('.listEntryEpisode').find('input').val());

  url = $(elt).attr('data-url');
  $.post(url, { "anime_entries[user_id]": user_id, "anime_entries[anime_id]": anime_id, "anime_entries[status]": status, "anime_entries[score]": score, "anime_entries[episode]": episode}).complete(function() {
    revertListEditForm(elt);
  });
}

function fetchAjax() {
  var remoteTarget = $(this).attr('data-url');
  var pageTarget = $(this).attr('data-target');

  // fade target.
  $(pageTarget).fadeTo("fast", 0.2);

  // fetch data.
  $(pageTarget).load(remoteTarget, function() {
    $(pageTarget).fadeTo("fast", 1);
    initInterface(pageTarget);
  });
  $(this).one('click', fetchAjax);
}

function loadAjaxTab(elt) {
  // loads the content for an AJAX nav-tab.
  var remoteTarget = $(elt).parent().attr('data-url');
  var pageTarget = $(elt).attr('href');

  // remove active marker from all other tab panes.
  $(pageTarget).parent().children('.tab-pane').each(function() {
    $(this).removeClass('active');
  });

  // load page from data-url if necessary.
  if (!$(elt).parent().hasAttr('loaded') && !$(elt).parent().hasAttr('loading')) {
    $(elt).parent().attr('loading', true);
    $(pageTarget).load(remoteTarget, function() {
      $(elt).parent().removeAttr('loading');
      $(elt).parent().attr('loaded', true);
      initInterface(pageTarget);
    });
  }
  $(pageTarget).addClass('active');
}

function mergeOptions(obj1, obj2) {
  var obj3 = {};
  for(var attrName in obj1) { obj3[attrName] = obj1[attrName]; }
  for(var attrName in obj2) { obj3[attrName] = obj2[attrName]; }
  return obj3;
}

function renderTimelines(elt) {
  // jqplot timelines.
  var globalSettings = {
    animate: true,
    seriesColors: ['#0064e1', '#00b9e1'],
    grid: {
      background: 'transparent',
      drawGridlines: false,
      drawBorder: false,
      borderColor: '#e5e5e5', //ticks
      shadow: false
    }, seriesDefaults: {
      shadow: false,
      //color: '#0064e1',
      rendererOptions: {
        barPadding: 0,
        barMargin: 0,
        barWidth: 15,
        highlightMouseDown: false
      }
    }, axesDefaults: {
      tickOptions: {
        fontSize: 10,
        fontFamily: 'Tahoma, sans-serif'
      }
    },
  };

  // render each timeline.
  $(elt).find('.timeline').each(function() {
    var data = [];
    var maximum = -100000000;
    var minimum = 100000000;
    var integerValues = true;
    var chartID = $(this).parent().attr('id');
    $(this).children('ul').children('li').each(function() {
      var x = $(this).text().split(',');
      var time = x[0];
      var value = parseFloat(x[1]);
      if ($(this).parent().children('li').length>7)
        time = '<span style="display: none">' + time + '</span>' + time;
      data.push([time, value]);
      if (value > maximum)
        maximum = value;
      if (value < minimum)
        minimum = value;
      if (value % 1 != 0) {
        integerValues = false;
      }
    });
    if (integerValues) {
      var highlightFormatString = '<p>%.0f</p>';
    } else {
      var highlightFormatString = '<p>%.2f</p>';
    }
    
    if (data.length > 0) {
      $(this).find('ul').replaceWith($('<div class="line-chart graph"></div>'));
      var globalMean = parseFloat($('.score-dist p.large').text());
      if (minimum < 0) {
        globalMin = 1.1 * minimum;
      } else {
        globalMin = 0.9 * minimum;
      }
      if (maximum < 0) {
        globalMax = 0.9 * maximum;
      } else {
        globalMax = 1.1 * maximum;
      }
      var data2 = [];
      for (i in data)
        data2[i] = [data[i][0], globalMean];
      var settings = {
        series: [
          {   color: '#cce0ff', 
            showMarker: false
          }, { 
            color: '#0064e1', 
            showMarker: true, 
            trendline: {
              show: false,
              type: 'linear'
            } 
          }
        ], axes: {
          xaxis: { renderer: $.jqplot.CategoryAxisRenderer, rendererOptions: { sortMergedLabels: false, drawBaseline: true }, tickOptions: { showMark: false } },
          yaxis: { min: globalMin, max: globalMax, tickOptions: { formatString: '%.2f', showMark: true } },
          labels: [ 'asd', 'asd' ]
        }, highlighter: {
          show: true,
          sizeAdjust: 5,
          lineWidthAdjust: 0,
          showMarker: true,
          tooltipLocation: 'n',
          tooltipAxes: 'y',
          formatString: highlightFormatString
        }
      };
      $.jqplot(chartID + ' div.graph',
        [data2, data],
        mergeOptions(globalSettings, settings));
    }
  });
}

function renderHistogram(elt) {
  var chartID = $(elt).attr('id');
  var chartDataID = chartID + "-csv";
  var data = d3.csv.parse(d3.select("#" + chartDataID).text());
  var valueLabelWidth = $(elt).hasAttr('data-valueLabelWidth') ? parseInt($(elt).attr('data-valueLabelWidth')): 40; // space reserved for value labels (right)
  var barHeight = $(elt).hasAttr('data-barHeight') ? parseInt($(elt).attr('data-barHeight')): 20; // height of one bar
  var barLabelWidth = $(elt).hasAttr('data-barLabelWidth') ? parseInt($(elt).attr('data-barLabelWidth')): 130; // space reserved for bar labels
  var barLabelPadding = $(elt).hasAttr('data-barLabelPadding') ? parseInt($(elt).attr('data-barLabelPadding')): 5; // padding between bar and bar labels (left)
  var gridLabelHeight = $(elt).hasAttr('data-gridLabelHeight') ? parseInt($(elt).attr('data-gridLabelHeight')): 18; // space reserved for gridline labels
  var gridChartOffset = $(elt).hasAttr('data-gridChartOffset') ? parseInt($(elt).attr('data-gridChartOffset')): 3; // space between start of grid and first bar
  var maxBarWidth = $(elt).hasAttr('data-maxBarWidth') ? parseInt($(elt).attr('data-maxBarWidth')): 275; // width of the bar with the max value
  var ticks = $(elt).hasAttr('data-ticks') ? parseInt($(elt).attr('data-ticks')): 5; // number of gridlines

  // accessor functions 
  var barLabel = function(d) { return d['Category']; };
  var barValue = function(d) { return parseFloat(d['Value']); };
   
  // scales
  var yScale = d3.scale.ordinal().domain(d3.range(0, data.length)).rangeBands([0, data.length * barHeight]);
  var y = function(d, i) { return yScale(i); };
  var yText = function(d, i) { return y(d, i) + yScale.rangeBand() / 2; };
  var x = d3.scale.linear().domain([0, d3.max(data, barValue)]).range([0, maxBarWidth]);

  // svg container element
  var chart = d3.select('#' + chartID).append("svg")
    .attr('width', maxBarWidth + barLabelWidth + valueLabelWidth)
    .attr('height', gridLabelHeight + gridChartOffset + data.length * barHeight);
  // grid line labels
  var gridContainer = chart.append('g')
    .attr('transform', 'translate(' + barLabelWidth + ',' + gridLabelHeight + ')'); 
  gridContainer.selectAll("text").data(x.ticks(ticks)).enter().append("text")
    .attr("x", x)
    .attr("dy", -3)
    .attr("text-anchor", "middle")
    .text(String);
  // vertical grid lines
  gridContainer.selectAll("line").data(x.ticks(ticks)).enter().append("line")
    .attr("x1", x)
    .attr("x2", x)
    .attr("y1", 0)
    .attr("y2", yScale.rangeExtent()[1] + gridChartOffset)
    .style("stroke", "#ccc");
  // bar labels
  var labelsContainer = chart.append('g')
    .attr('transform', 'translate(' + (barLabelWidth - barLabelPadding) + ',' + (gridLabelHeight + gridChartOffset) + ')'); 
  labelsContainer.selectAll('text').data(data).enter().append('text')
    .attr('y', yText)
    .attr('stroke', 'none')
    .attr('fill', 'black')
    .attr("dy", ".35em") // vertical-align: middle
    .attr('text-anchor', 'end')
    .text(barLabel);
  // bars
  var barsContainer = chart.append('g')
    .attr('transform', 'translate(' + barLabelWidth + ',' + (gridLabelHeight + gridChartOffset) + ')'); 
  barsContainer.selectAll("rect").data(data).enter().append("rect")
    .attr('y', y)
    .attr('height', yScale.rangeBand())
    .attr('width', function(d) { return x(barValue(d)); })
    .attr('stroke', 'white')
    .attr('fill', 'steelblue');
  // bar value labels
  barsContainer.selectAll("text").data(data).enter().append("text")
    .attr("x", function(d) { return x(barValue(d)); })
    .attr("y", yText)
    .attr("dx", 3) // padding-left
    .attr("dy", ".35em") // vertical-align: middle
    .attr("text-anchor", "start") // text-align: right
    .attr("fill", "black")
    .attr("stroke", "none")
    .text(function(d) { return d3.round(barValue(d), 2); });
  // start line
  barsContainer.append("line")
    .attr("y1", -gridChartOffset)
    .attr("y2", yScale.rangeExtent()[1] + gridChartOffset)
    .style("stroke", "#000");
}

function renderGraphs(elt) {
  // renders all the graphs.
  renderTimelines(elt);
}
/*
function siteTour(elt, start, end) {
  start = (typeof start === "undefined" || end === null) ? 1 : parseInt(start);
  end = (typeof end === "undefined" || end === null) ? $('.tour-step').length : parseInt(end);

  // get all tour elements.
  var tourSteps = {};
  $(elt).find('.tour-step').each(function() {
    tourSteps[$(this).attr('data-step')] = $(this).attr('id');
    $(this).attr('data-placement', $(this).hasAttr('data-placement') ? $(this).attr('data-placement') : 'bottom');
  });

  console.log(tourSteps);

  var tour = new Tour();
  // add steps to tour within this range.
  for (var step = start; step <= end; step++) {
    if (step in tourSteps) {
      tourElt = $('#' + tourSteps[step]);
      tour.addStep({
        element: '#' + tourSteps[step],
        placement: $(tourElt).attr('data-placement'),
        title: $(tourElt).attr('data-title'),
        content: $(tourElt).attr('data-content')
      });
    }
  }
  tour.start();
}
*/

function updateTitleItems(numItems) {
  var numPrevNewFeedItems = (document.title.match(/\([0-9]+\)/i) == null) ? 0 : parseInt(document.title.match(/[0-9]+/i)[0]);
  document.title = document.title.replace(/\([0-9]+\)\ /i, '');
  if (!isNaN(numPrevNewFeedItems)) {
    document.title = "(" + (numItems + numPrevNewFeedItems) + ") " + document.title;
  } else {
    document.title = "(" + numItems + ") " + document.title;
  }
}

function updateFeedTimes() {
  //updates each feed item's displayed time.
  var currentTime = Math.round(Date.now()/1000);
  $('.feedDate').each(function() {
    var timeDiff = currentTime - parseInt($(this).attr('data-time'));
    if (timeDiff < 60) {
      $(this).text(timeDiff + "s");
    } else if (timeDiff < 3600) {
      $(this).text(Math.floor(timeDiff/60) + "min");
    } else if (timeDiff < 86400) {
      $(this).text(Math.floor(timeDiff/3600) + "h");
    } else if (timeDiff < 604800) {
      $(this).text(Math.floor(timeDiff/86400) + "d");   
    } else if (timeDiff < 2629744) {
      $(this).text(Math.floor(timeDiff/604800) + "wk");
    } else if (timeDiff < 31556926) {
      $(this).text(Math.floor(timeDiff/2629744) + "mo");    
    }  else {
      $(this).text(Math.floor(timeDiff/31556926) + "yr");   
    } 
  });
}

function ScrollHandler(e) {
  //throttle scroll event.
  var elt = e.data.elt;
  clearTimeout(scrollThrottleTimer);
  scrollThrottleTimer = setTimeout(function () {
    // check that we haven't scrolled to the bottom of an ajax feed.
    $(elt).find('ul.ajaxFeed:visible').each(function() {
      if ($(this).children('li').length > 0 && $(this).height() <= ($(window).height() + $(window).scrollTop()) && !$(this).hasAttr('loading')) {
        var feedNode = this;
        //get last-loaded list change and load more past this.
        $(feedNode).attr('loading', 'true');
        // find the lowest feedDate on the page.
        var feedDates = $(feedNode).find('.feedDate').map(function() {
          return $(this).attr('data-time');
        }).get();
        var lastTime = Array.min(feedDates);
        var anime_id = $(feedNode).hasAttr('anime_id') ? $(feedNode).attr('anime_id') : "";
        var user_id = $(feedNode).hasAttr('user_id') ? $(feedNode).attr('user_id') : "";
        if ($(feedNode).attr('data-url').indexOf('?') < 0) {
          joinChar = '?';
        } else {
          joinChar = '&';
        }
        $.ajax({
          url: $(feedNode).attr('data-url') + joinChar + "maxTime=" + encodeURIComponent(lastTime) + ((anime_id != "" && !isNaN(parseInt(anime_id))) ? '&anime_id=' + parseInt(anime_id) : "") + ((user_id != "" && !isNaN(parseInt(user_id))) ? '&user_id=' + parseInt(user_id) : ""),
          data: {},
          success: function(data) {
            $(feedNode).append($(data).html());
            initInterface($(feedNode).parent());
            $(feedNode).removeAttr('loading');
          }
        });
      }
    });
  }, scrollThrottleDelay);  
}

function initInterface(elt) {
  // initializes all interface elements and events within a given element.

  // parse URL parameters.
  var urlParams;
  (window.onpopstate = function () {
      var match,
          pl     = /\+/g,  // Regex for replacing addition symbol with a space
          search = /([^&=]+)=?([^&]*)/g,
          decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
          query  = window.location.search.substring(1);

      urlParams = {};
      while (match = search.exec(query))
         urlParams[decode(match[1])] = decode(match[2]);
  })();

  // window scroll events.
  $(window).off('scroll', ScrollHandler)
    .on('scroll', {elt: elt}, ScrollHandler);

  $(elt).find('.dropdown-toggle').dropdown();
  $(elt).tooltip({
    selector: "a[data-toggle=tooltip]"
  });
  /* Datatable initialisation */
  $(elt).find('.dataTable').each(function() {
    initDataTable(this);
  });

  renderGraphs(elt);

  /* Disable buttons on click */
  $(elt).find('form').each(function() {
    $(this).submit( function() {
      $(this).children('input[type=submit], button').each(function() {
        $(this).attr('disabled', 'disabled');
        $(this).addClass("disabled");
        if (!$(this).hasAttr('data-loading-text')) {
          $(this).text("Loading...");
        } else {
          $(this).text($(this).attr('data-loading-text'));
        }
      });
    });
  });

  /* token input initialization */
  $(elt).find('.token-input').each(function() {
    tokenLimit = $(this).hasAttr('data-tokenLimit') ? $(this).attr('data-tokenLimit') : null;
    prePopulated = $(this).hasAttr('data-value') ? $.secureEvalJSON($(this).attr('data-value')) : null;
    $(this).tokenInput($(this).attr("data-url"), {
      queryParam: "term",
      minChars: 3,
      theme: "facebook",
      preventDuplicates: true,
      propertyToSearch: $(this).attr("data-field"),
      prePopulate: prePopulated,
      tokenLimit: tokenLimit
    });
  });

  /* autocomplete initialization */
  $(elt).find('.autocomplete').each(function() {
    valueField = $(this).hasAttr('data-valueField') ? $(this).attr('data-valueField') : "value";
    labelField = $(this).hasAttr('data-labelField') ? $(this).attr('data-labelField') : "label";
    outputElement = $(this).hasAttr('data-outputElement') ? $(this).attr('data-outputElement') : $(this);
    $(this).autocomplete({
        source: $(this).attr('data-url'),
        minLength: 3,
        response: function(event, ui) {
          $.each(ui.content, function() {
            $(this).attr("label", $(this).attr(labelField));
            $(this).attr("value", $(this).attr(valueField));
          });
        },
        select: function(event, ui) {
          $(this).val(ui.item.label);
          $(outputElement).val(ui.item.value);
          event.preventDefault();
        }
    });
  });

  // for autocomplete fields that shrink upon entry selection and display a hidden neighbor
  // e.g. on inline list entry forms
  $(".autocomplete-shrink").each(function() {
    $(this).bind('autocompleteselect', function(e, ui) {
      $(this).val(ui.item.label);
      $($(this).attr('data-outputElement')).val(ui.item.value);

      // fetch latest status for this user.
      var statusUrl = $(this).attr('data-status-url') + "?anime_id=" + ui.item.value;
      var statusContainer = $(this).parent().next();
      $.getJSON(statusUrl, function(data) {
        if (typeof data["status"] != "undefined" && data["status"] != "0") {
          $(statusContainer).find('#anime_entries\\[status\\]').val(data["status"]);
        } else {
          $(statusContainer).find('#anime_entries\\[status\\]').val(1);
        }
        if (typeof data["score"] != "undefined" && data["score"] != "0") {
          $(statusContainer).find('#anime_entries\\[score\\]').val(data["score"]);
        } else {
          $(statusContainer).find('#anime_entries\\[score\\]').val("");
        }
        if (typeof data["episode"] != "undefined" && data["episode"] != "0") {
          $(statusContainer).find('#anime_entries\\[episode\\]').val(data["episode"]);
        } else {
          $(statusContainer).find('#anime_entries\\[episode\\]').val("");
        }
        if (typeof data["episode_count"] != "undefined" && data["episode_count"] != "0") {
          $(statusContainer).find('#anime_entries\\[episode\\]').next().text("/" + data["episode_count"].toString());
        } else {
          $(statusContainer).find('#anime_entries\\[episode\\]').next().text("/?");
        }
      });
      $(this).parent().removeClass('col-md-12').addClass('col-md-3');
      $(statusContainer).fadeIn().addClass('col-md-9');
    });
  });

  /* ajax feed autoloading. */
  $(elt).find('ul.ajaxFeed:visible').each(function() {
    var feedNode = this;
    if ($(this).attr('data-url').indexOf('?') < 0) {
      joinChar = '?';
    } else {
      joinChar = '&';
    }
    interval.clear($(feedNode).id);
    interval.make(function() {
      var feedDates = $(feedNode).children().map(function() {
        return $(this).find('div.feedDate').attr('data-time');
      }).get();
      var lastTime = Array.max(feedDates);
      $.ajax({
        url: $(feedNode).attr('data-url') + joinChar + 'minTime=' + encodeURIComponent(lastTime)
      }).done(function(data) {
        if (data.length > 0) {
          if ($('body').attr('blurred') == "true") {
            updateTitleItems(data.match(/\<li/gi).length);
          }
          $(feedNode).prepend($(data).children('li'));
          initInterface($(feedNode).parent());
        }
      });
      updateFeedTimes();
    }, 10000, $(feedNode).id);
  });

  /* ajax tab autoloading. */
  $(elt).find('.nav-tabs li.ajaxTab:visible').each(function() {
    $(this).click(function(e) {
      e.preventDefault();
      location.hash = $(e.target).attr('href').substr(1);
      loadAjaxTab(e.target); // activated tab
    });
  });

  /* ajax link autoloading. */
  $(elt).find('.ajaxLink').each(function() {
    $(this).one('click', fetchAjax);
    $(this).click(function(e) {
      e.preventDefault();
      $(e.target).one('click', fetchAjax);
    });
  })

  /* feed entry menu initialization */
  $(elt).find('.feedEntry').each(function() {
    $(this).hover(function() {
      $(this).find('.feedEntryMenu').removeClass('hidden');
    }, function() {
      $(this).find('.feedEntryMenu').addClass('hidden');
    });
  });

  // fills episode count automatically when user marks anime as completed in dropdown AND the total episode count has already been loaded as an input add-on.
  $('select[name=anime_list\\[status\\]]').each(function() {
    $(this).change(function() {
      if ($(this).val() == "2") {
        var episodeNode = $($(this).parent().parent().parent().find('input[name=anime_list\\[episode\\]]')[0]);
        if (episodeNode.next().length > 0) {
          var episodeCount = parseInt(episodeNode.next().text().substr(1));
          episodeNode.val(episodeCount);
        }
      }
    });
  });

  /* animelist edit link events */
  $(elt).find(".listEdit").each(function() {
    // allows users to update entries from their list.
    $(this).click(function(event) {
      displayListEditForm(this);
      event.preventDefault();
    });
  });

  /* Load a specific tab if url contains a hash */
  var url = document.location.toString();
  if (url.match('#')) {
    thisTab = $(elt).find('.nav-tabs a[href=#'+url.split('#')[1]+']');
    if (thisTab.length > 0) {
      thisTab.tab('show');
      loadAjaxTab(thisTab);
    }
  }

  // // start tour if requested.
  // if ("tour" in urlParams && $(elt).find('.tour-step').length > 0) {
  //   start = "tourStart" in urlParams ? parseInt(urlParams["tourStart"]) : 1;
  //   end = "tourEnd" in urlParams ? parseInt(urlParams["tourEnd"]) : null;
  //   siteTour(elt, start, end);
  // }
}

$(document).ready(function () {
  initInterface(document);
});