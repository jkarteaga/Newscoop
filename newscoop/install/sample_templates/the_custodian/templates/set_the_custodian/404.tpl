{{ include file="_tpl/_html-head.tpl" }}

<body id="sectionpage">

  <div id="container">
          
{{ include file="_tpl/header.tpl" }}
    
    <div class="row clearfix" role="main">
  
      <div id="maincol" class="eightcol clearfix">
        
{{ if !($gimme->url->is_valid) }}
  <h1>Sorry, the requested page was not found.</h1>
{{ /if }}                        

        </div><!-- /#maincol -->
        
    <div id="sidebar" class="fourcol last">

{{ include file="_tpl/sidebar-loginbox.tpl" }}

{{ include file="_tpl/sidebar-most.tpl" }} 
            
{{ include file="_tpl/sidebar-community-feed.tpl" }}    
            
{{ include file="_tpl/_banner-sidebar.tpl" }} 
            
        </div><!-- /#sidebar -->
        
    </div>
    
{{ include file="_tpl/footer.tpl" }}

  </div> <!-- /#container -->
  
{{ include file="_tpl/_html-foot.tpl" }}