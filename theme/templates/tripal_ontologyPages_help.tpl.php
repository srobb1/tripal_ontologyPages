<h2>Tripal Ontology Pages Help</h2>

<h3>Where to get the code</h3>
<p>The code can be found on <a href="https://github.com/srobb1/tripal_ontologyPages">github</a></p>


<h3>About</h3>
<p>This is a module that generates an overview page for an ontology term that is hosted at EBI OLS. When you use a link in the following format (https://[your_stie]/ontology/PLANA_0000475) a page is automatically generated with information pulled dynamically from the EBI OLS. PLANA is the ontology Prefix and the numerical values are the ID</p>

<h3>Too Slow</h3>
<p>If it is taking too long for your pages to load dynamically, you can download all the terms of a ontology of interest to your file system.<br>
https://[yoursite]/ontology-update-json/PREFIX<br>
for example: https://[yoursite]/ontology-update-json/PLANA<br>
</p>

<h3>Ontology Page Format</h3>
<p>Is the format or layout of the ontology page not to your liking? Make a new template file in your themes template dir with the name: tripal_ontologyPages.tpl.php</p>

<h3>Questions</h3>
<p>Post Questions or Issues to the <a href="https://github.com/srobb1/tripal_ontologyPages/issues">GitHub Page</a></p>
