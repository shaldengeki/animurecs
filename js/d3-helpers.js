function getDate(d) {
    return new Date(d.x);
}
function getMonthYear(d) {
  return d3.time.format("%m/%Y").parse(d.x);
}

function getIDName(id, type) {
  fullName = $('#' + type + ' option[value=' + id + ']').text();
  return fullName.split('_').slice(-2).join("_");
}

function displayFormFieldLineGraph(data, title, div_id) {
  var getPointY = function(d) { return d.y; };
  var getLineMinX = function(d) { return getMonthYear(d[0]); };
  var getLineMaxX = function(d) { return getMonthYear(d[d.length-1]); };
  var getLineMinY = function(d) { return d3.min(d, getPointY); };
  var getLineMaxY = function(d) { return d3.max(d, getPointY); };

  // get max and min dates - this assumes data is sorted
  var minDate = getDate(data[0]),
      maxDate = getDate(data[data.length-1]),
      minValue = Math.min.apply(Math,data.map(function(o){return o.y;})),
      maxValue = Math.max.apply(Math,data.map(function(o){return o.y;}));
  var w = 300,
  h = 200,
  p = 30,
  y = d3.scale.linear().domain([d3.min(data, getPointY), d3.max(data, getPointY)]).range([h,25]),
  x = d3.time.scale().domain([minDate, maxDate]).range([0, w]);
  num_ticks_x = 10;
  num_ticks_y = 10;

  var vis = d3.select("#"+div_id)
  .data([data])
    .append("svg:svg")
    .attr("width", w + p * 2)
    .attr("height", h + p * 2)
    .append("svg:g")
    .attr("transform", "translate(" + p + "," + p + ")");

  var rules = vis.selectAll("g.rule")
    .data(x.ticks(num_ticks_x))
    .enter().append("svg:g")
     .attr("class", "rule");

  // Draw grid lines
  rules.append("svg:line")
    .attr("x1", x)
    .attr("x2", x)
    .attr("y1", 25)
    .attr("y2", h - 1);

  rules.append("svg:line")
    .attr("class", function(d) { return d ? null : "axis"; })
    .data(y.ticks(num_ticks_y))
      .attr("y1", y)
      .attr("y2", y)
      .attr("x1", 0)
      .attr("x2", w);

  // Place axis tick labels
  rules.append("svg:text")
    .attr("x", x)
    .attr("y", h + 15)
    .attr("dy", ".71em")
    .attr("text-anchor", "middle")
    .text(x.tickFormat(num_ticks_x));
  rules.append("svg:text")
    .data(y.ticks(num_ticks_y))
      .attr("y", y)
      .attr("x", -10)
      .attr("dy", ".35em")
      .attr("text-anchor", "end")
      .text(y.tickFormat(num_ticks_y));
    
  vis.append("svg:path")
  .attr("class", "line")
  .attr("d", d3.svg.line()
      .x(function(d) { return x(getDate(d)) })
      .y(function(d) { return y(d.y) }))
  .attr("stroke", "maroon");

  vis.selectAll("circle.line")
  .data(data)
  .enter().append("svg:circle")
  .attr("class", "line")
  .attr("cx", function(d) { return x(getDate(d)) })
  .attr("cy", function(d) { return y(d.y); })
  .attr("r", 2.5);
  
  vis.append("svg:text")
     .attr("x", w/4)
     .attr("y", 20)
     .text(title);
}

/* line d3 plots */
function drawLargeD3Plot() {
  machines = jQuery.map($("#machines option:selected"), function(a) {
    return a.value;
  });
  form_fields = jQuery.map($("#form_fields option:selected"), function(a) {
    return a.value;
  });
  if (machines.length <= 0 || form_fields.length <= 0) {
    return;
  }
  var getPointY = function(d) { return d.y; };
  var getLineMinX = function(d) { return getMonthYear(d[0]); };
  var getLineMaxX = function(d) { return getMonthYear(d[d.length-1]); };
  var getLineMinY = function(d) { return d3.min(d, getPointY); };
  var getLineMaxY = function(d) { return d3.max(d, getPointY); };
  d3.json("../graph.php?action=json&form_fields=" + form_fields.join(",") + "&machines=" + machines.join(","), function(json) {
    $('#vis').empty();
    h = 400;
    w = 1000;
    p = 30;
    legend_width = 100;
    legend_entry_height = 30;
    graph_padding = 0.1;
    graph_domain_y = d3.max(json, getLineMaxY) - d3.min(json, getLineMinY);
    num_x_ticks = 15;
    num_y_ticks = 10;
    colors = d3.scale.category20();
    
    x = d3.time.scale().domain([d3.min(json, getLineMinX), d3.max(json, getLineMaxX)]).range([0, w - legend_width]);
    y = d3.scale.linear().domain([d3.min(json, getLineMinY) - (graph_padding * graph_domain_y), d3.max(json, getLineMaxY) + (graph_padding * graph_domain_y)]).range([h,0]);
    
    var vis = d3.select("#vis")
      .append("svg:svg")
      .attr("width", w + p * 2)
      .attr("height", h + p * 2)
      .append("svg:g")
      .attr("transform", "translate(" + p + "," + p + ")");

    var rules = vis.selectAll("g.rule")
      .data(x.ticks(num_x_ticks))
      .enter().append("svg:g")
        .attr("class", "rule");

    // Draw grid lines
    rules.append("svg:line")
      .attr("x1", x)
      .attr("x2", x)
      .attr("y1", 0)
      .attr("y2", h - 1);

    rules.append("svg:line")
      .attr("class", function(d) { return d ? null : "axis"; })
      .data(y.ticks(10))
        .attr("y1", y)
        .attr("y2", y)
        .attr("x1", 0)
        .attr("x2", w - legend_width);

    // Place axis tick labels
    rules.append("svg:text")
      .attr("x", x)
      .attr("y", h + 15)
      .attr("dy", ".71em")
      .attr("text-anchor", "middle")
      .text(x.tickFormat(num_x_ticks));
    rules.append("svg:text")
      .data(y.ticks(num_y_ticks))
        .attr("y", y)
        .attr("x", -10)
        .attr("dy", ".35em")
        .attr("text-anchor", "end")
        .text(y.tickFormat(num_y_ticks));

    // Place legend
    for (i = 0; i < json.length; i++) {
      vis.append("svg:rect")
       .attr("fill", colors(i) )
       .attr("x", w - legend_width + 10)
       .attr("y", i*legend_entry_height )
       .attr("width", legend_entry_height * 2/3)
       .attr("height", legend_entry_height * 2/3);
       
      vis.append("svg:text")
       .attr("x", w + legend_entry_height - legend_width + 10)
       .attr("y", i*legend_entry_height+(legend_entry_height/2))
       .text(getIDName(json[i][0]['machine'], 'machines') + "-" + getIDName(json[i][0]['field'], 'form_fields'));
    }

    var line = d3.svg.line()
      .interpolate("linear")
      .x(function(d) { return x(getMonthYear(d)); })
      .y(function(d) { return y(d.y); });
      
    var circle = d3.svg.arc()
      .innerRadius(0)
      .outerRadius(3.5)
      .startAngle(0)
      .endAngle(7)
    
    vis.selectAll(".line")
      .data(json)
      .enter().append("path")
        .attr("class", "line")
        .attr("stroke", function(d, i) { return colors(i); })
        .attr("stroke-width", 2)
        .attr("d", line);
    
    circleData = [];
    for (i = 0; i < json.length; i++) {
      circleData.push.apply(circleData, json[i])
    }
    vis.selectAll("circle.line")
      .data(circleData)
      .enter().append("svg:circle")
        .attr("class", "line")
        .attr("d", circle)
        .attr("cx", function(d) { return x(getMonthYear(d)); })
        .attr("cy", function(d) { return y(d.y); })
        .attr("r", 2.5);
  });
}