function postAjax(url, data, success)
  {
  var params = typeof data == 'string' ? data : Object.keys(data).map(
    function(k)
    {
    return encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
    }
  ).join('&');

  var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  xhr.open('POST', url);
  xhr.onreadystatechange = function()
    {
    if (xhr.readyState > 3 && xhr.status == 200)
      {
      success(xhr.responseText);
      }
    };
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.send(params);
  return xhr;
  }

function getAjax(url, success)
  {
  var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject(
    'Microsoft.XMLHTTP');
  xhr.open('GET', url);
  xhr.onreadystatechange = function()
    {
    if (xhr.readyState > 3 && xhr.status == 200) success(xhr.responseText);
    };
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.send();
  return xhr;
  }

// pretty unsafe escape function
function escapeHtml(unsafe)
  {
  return unsafe
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
  }

var NextEventWidgetAPI = window.NextEventWidgetAPI;

var basket_update = function(data)
  {
  var order_id = data.order_id;
  var url = 'server.php';
  var post_data = {set_order_id: order_id};
  postAjax(url, post_data, function()
    {
    console.log('order added', post_data);
    })
  };

NextEventWidgetAPI.addMessageHandler('basket_update', basket_update);


// var current_step = function(data)
//   {
//   if (data.step.indexOf('payment') >= 0 || data.step.indexOf('checkout') >= 0)
//     {
//     // hide widget
//     var widgets = document.querySelectorAll('.nextevent');
//     widgets.forEach(function(widget)
//       {
//       widget.style.display = 'none';
//       });
//     // redirect
//     window.location.href = 'checkout.php';
//     }
//   };
//
// NextEventWidgetAPI.addMessageHandler('current_step', current_step);


var close_widget = function(data)
{
  // redirect
  window.location.href = 'checkout.php';
};

NextEventWidgetAPI.addMessageHandler('close_widget', close_widget);


/**
 * create ticket list
 *
 * @param {Array} urls
 */
function showTickets(urls)
  {
  var loader = document.querySelector('.load-msg');
  loader.style.display = 'none';

  var list = document.querySelector('.ticket-list');
  urls.forEach(function (url)
    {
    var div = document.createElement('div');
    div.innerHTML = '<div class="panel panel-default"><div class="panel-body">' +
                   '<a href="' + url + '"><i class="fa fa-ticket"></i> Ticket(s) herunterladen\n' +
                   '</div></div>';
    list.appendChild(div);
    });

  list.style.display = 'block';
  }


if (window.pollTickets)
  {
  var polling = true;
  var pollId;

  var responseHandler = function(data)
    {
    polling = false;
    var response = JSON.parse(data);
    if (response.ready)
      {
      clearInterval(pollId);
      showTickets(response.urls);
      }
    };

  getAjax('server.php?tickets', responseHandler);

  pollId = setInterval(function()
    {
    if (polling) return;
    polling = true;
    getAjax('server.php?tickets', responseHandler);
    }, 2000)
  }

