function block_elisdashboard_expand(id) {
    wrapperid = 'inst'+id;
    var ele = document.getElementById(wrapperid);
    var offsettop = ele.offsetTop;
    ele.className = ele.className + ' block_elisdashboard_expanded';
    ele.style.top = offsettop+"px";
}

function block_elisdashboard_unexpand(id) {
    wrapperid = 'inst'+id;
    var ele = document.getElementById(wrapperid);
    ele.className = ele.className.replace('block_elisdashboard_expanded', '');
    ele.style.top = '';
}