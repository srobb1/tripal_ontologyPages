PREFIX=$1

if [ "$PREFIX" != "" ] ; then
  
  if [ ! -d /var/www/html/sites/default/files/tripal/tripal_OntologyPages ] ; then
    echo "making new directory for ontolgoy json pages"
    mkdir /var/www/html/sites/default/files/tripal/tripal_ontologyPages
  fi
  
  cd /var/www/html/sites/default/files/tripal/tripal_ontologyPages 
  
  curl "https://www.ebi.ac.uk/ols/api/ontologies/$PREFIX" -i -H 'Accept: application/json' > $PREFIX.latest
  
  
  LATEST=`grep -P '"version".+\d{4}\-\d{2}\-\d{2}' $PREFIX.latest | perl -p -e 's/.+(\d{4}\-\d{2}\-\d{2}).+/$1/'`
  if [ ! -e $PREFIX.last ] ; then echo 'none' > $PREFIX.last ; fi
  LAST=`cat $PREFIX.last`
  
  if [  $LATEST != $LAST  ] ; then
    #echo "Last Version: $LAST<br>"
    #echo "New Version Available: $LATEST<br>"
    VERSION=`echo $LATEST | perl -p -e 's/v//'`
    IRI=`grep versionIri $PREFIX.latest | perl -p -e 's/.*"versionIri" : "(http\S+)",.*/$1/'` 
    #echo "IRI: $IRI<br>"
    curl -OL $IRI
    OWL=`echo $IRI | perl -p -e 's/.*\/([^\/]+\.owl).*/$1/'`
    #echo "OWL: $OWL"
    REGEX="'s/.+(${PREFIX}_\d+).+/\$1/i'"
    for i in `grep -i ${PREFIX}_ $OWL  |  perl -s -p -e 's/.+(${prefix}_\d+).+/$1/i' -- -prefix=$PREFIX` ; do curl -OL  https://www.ebi.ac.uk/ols/api/ontologies/$PREFIX/terms?iri=http://purl.obolibrary.org/obo/$i ; curl --fail -o $i.graph  https://www.ebi.ac.uk/ols/api/ontologies/$PREFIX/terms/http%253A%252F%252Fpurl.obolibrary.org%252Fobo%252F$i/graph ; done
     echo "MESSAGE: Done"
  else
    echo "MESSAGE: LAST version: $LAST is still current"
  fi
  echo $LATEST > $PREFIX.last
else
  echo "ERROR: Please supply a prefix: http://yoursite/ontology-update-json/plana"
fi
