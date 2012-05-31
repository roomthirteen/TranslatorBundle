var expr = /\[T id="([^"]*)" domain="([^"]*)" locale="([^"]*)"\](.*?)\[\/T\]/;


function handleNode()
{

    var node = this;

    // node content
    if(node.firstChild !== null && node.firstChild.nodeType === 3) { // Node.TEXT_NODE === 3
        var content = node.firstChild.nodeValue;
        var result = checkTranslatableText(content, node);
        if(false !== result) {
            node.firstChild.nodeValue = result;
        }
    }


    return;
    // node attributes
    var matched = false;
    $.each(node.attributes, function()
    {
        var attribute = this;
        var result = checkTranslatableText(attribute.value, node);
        if(false !== result)
        {
            attribute.value = result;
        }
    });
}

function checkTranslatableText(text, node)
{
    var matches = text.match(expr);



    if(matches==null) return false;

    nodes.push(node);
    $(node).addClass('translatable');

    return matches[1];
}


function initNode()
{
    var input = $('<input type="text" class="translatable-input" />');

    input
        .width($(this).innerWidth())
        .height($(this).innerHeight())
        .offset($(this).offset())
        .val($(this).text())
        .click(function(){ $(this).select(); })

    ;



    this.append(input);



    if($(this).offsetParent().css('position')=="static")
    {
        input.css('position','fixed');
    }
    else
    {
        console.log("absolute");
        input.css('position','absolute');
    }
}

var nodes = [];

$('*').each(handleNode);
$.each(nodes,initNode);


