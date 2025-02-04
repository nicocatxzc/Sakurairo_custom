<?php
//Sakura样式
?>
.site-header {
    width: 100%;
    height: 75px;
    top: 0;
    left: 0;
    background: 0 0;
    -webkit-transition: all .4s ease;
    transition: all .4s ease;
    position: fixed;
    z-index: 999;
    border-radius: 0px;
}
.header-user-avatar {
  margin-top: 22px;
}
.site-branding {
  height: 75px;
  line-height: 75px;
}
.site-title img {
  margin-top: 17px;
}
.site-top .lower {
  margin: 15px 0 0 0;
}
.lower li ul {
  top: 46px;
  right: -24px;
}
.header-user-menu {
  right: -11px;
  top: 44px;
}
.logolink a {
  height: 56px;
  line-height: 56px;
}
.logolink.moe-mashiro a{
  line-height: 56px !important;
}
.searchbox.js-toggle-search{
  margin: 17px 0;
  margin-left: 15px;
}
@media (max-width:860px) {
.site-header {
  height: 60px;
}
}