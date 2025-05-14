# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

######## Place your settings below ########

$smwgSparqlRepositoryConnector = 'fuseki';
$smwgSparqlEndpoint["query"] = 'http://fuseki:3030/ds/sparql';
$smwgSparqlEndpoint["update"] = 'http://fuseki:3030/ds/update';
