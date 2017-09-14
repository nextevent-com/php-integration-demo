/**
 * Util for posting data as JSON to backend by post message
 *
 * @param {string} url
 * @param {string|object} data query parameter
 * @param {function} success callback on success
 * @return {*}
 */
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

/**
 * Util for fetching data as JSON from backend
 *
 * @param {string} url
 * @param {function} success callback on success
 * @return {*}
 */
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

// pretty unsafe escape function, don't use this for production
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

// save order id in session
var basket_update = function(data)
  {
  var orderId = data.order_id;
  var basketChangedUrl = 'server.php';
  var postData = {set_order_id: orderId};
  postAjax(basketChangedUrl, postData, function()
    {
    console.log('order added', postData);

    // update the basket view
    var basketUrl = 'server.php?basket';
    getAjax(basketUrl, function (response)
      {
      var data = {};
      try
        {
        data = JSON.parse(response);
        }
      catch (err)
        {
        data.error = JSON.stringify(err);
        }
      // replace basket
      var basketElement = window.document.getElementById('basket');
      if (data.html)
        {
        basketElement.innerHTML = data.html;
        }
      if (data.error)
        {
        basketElement.innerHTML += '<div class="alert alert-danger"><strong>ERROR</strong> ' + data.error + '</div>\n';
        }
      });
    });
  };

NextEventWidgetAPI.addMessageHandler('basket_update', basket_update);

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

