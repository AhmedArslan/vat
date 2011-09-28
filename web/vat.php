<?php 
/**
 * Web-based viewer program of the [Variant Annotation Tools][ref-vat].
 * 
 * [ref-vat]: http://vat.gersteinlab.org
 * 
 * @package    VAT
 * @author     David Z. Chen
 * @copyright  (c) 2011 Gerstein Lab
 * @license    ???
 */

require_once 'lib/config.php';
require_once 'lib/util.php';
require_once 'lib/vatutil.php';
require_once 'lib/vcf.php';
require_once 'lib/aws/sdk.class.php';

/**
 * 
 */

function hyperlink_id($id)
{
    if (strstr($id, 'rs') === FALSE)
        return $id;
    
    $tokens = explode(';', $id);
    $str = "";
    for ($i = 0; $i < count($tokens); $i++)
    {
        if (strstr($tokens[$i]) !== FALSE)
        {
            $str .= '<a href="http://www.ncbi.nlm.nih.gov/SNP/snp_ref.cgi?rs='
                    .substr($tokens[$i], 2).'" target="external">'.$tokens[$i].'</a>';
        }
        else 
        {
            $str .= $tokens[$i];
        }
        
        $str .= ($i < count($tokens) - 1) ? ';' : '';
    }
    
    return $str;
}

/**
 * Returns a JSON string  containing gene summary information
 * 
 * @param string $data_set
 * @param $annotation_set
 * @param string $type
 * @return array
 */
function gene_summary_json($data_set, $annotation_set, $type)
{
    global $vat_config;
    
    $gene_summary = array(
        'aaData'    => array(),
        'aoColumns' => array()
    );
    
    $file = $vat_config['WEB_DATA_DIR'].'/'.$data_set.'.geneSummary.txt';
    if (($fp = fopen($file, 'r')) === FALSE)
    {
        return FALSE;
    }
    
    $header = fgets($fp);
    $tokens = explode("\t", $header);
    foreach ($tokens as $token)
    {
        array_push($gene_summary['aoColumns'], array('sTitle' => $token));
    }
    array_push($gene_summary['aoColumns'], array('sTitle' => 'Link'));
    
    while (($line = fgets($fp)) !== FALSE)
    {
        $line_array = explode("\t", $line);
        $gene_id = $line_array[0];
        
        if ($type == "coding")
        {
            $link = '<a href="vat.php?mode=showGene&dataSet='.$data_set
                   .'&annotationSet='.$annotation_set.'&geneId='.$gene_id.'"'
                   .' target="gene">Link</a>';
            array_push($line_array, $link);
        }
        elseif ($type == "nonCoding")
        {
            $link = '<a href="vat.php?mode=showNonCoding&dataSet='.$data_set
                   .'&annotationSet='.$annotation_set.'&geneId='.$gene_id.'"' 
                   .'target="gene">Link</a>';
            array_push($line_array, $link);
        }
        else
        {
            echo "Unknown type ".$type;
            return FALSE;
            // XXX Better to throw exception
        }
        
        array_push($gene_summary['aaData'], $line_array);
    }
    
    return json_encode($gene_summary);   
}

/**
 * Returns a JSON string containing sample sumary information
 * 
 * @param $data_set
 * @return array
 */
function sample_summary_json($data_set)
{
    global $vat_config;
    
    $sample_summary = array(
        'aaData'    => array(),
        'aoColumns' => array(),
    );
    
    $file = $vat_config['WEB_DATA_DIR'].'/'.$data_set.'.sampleSummary.txt';
    if (($fp = fopen($file, 'r')) === FALSE)
    {
        return FALSE;
    }
    
    $header = fgets($fp);
    $tokens = explode("\t", $header);
    foreach ($tokens as $token)
    {
        array_push($sample_summary['aoColumns'], array('sTitle' => $token));
    }
    
    while (($line = fgets($fp)) !== FALSE)
    {
        $tokens = explode("\t", $line);
        array_push($sample_summary['aaData'], $tokens);
    }
    
    return json_encode($sample_summary);
}

/**
 * Controller/view for process data page
 * 
 * @param $data_set
 * @param $annotation_set
 * @param string $gene_id
 */
function process_data($data_set, $annotation_set, $gene_id)
{
    global $vat_config;
    ?>
<head>
	<meta charset="utf-8">
	<title>VAT</title>
	<link rel="stylesheet" href="css/style.css" />
	<style type="text/css" media="screen">
		@import url(http://www.datatables.net/release-datatables/media/css/demo_table.css);
	</style>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
	<script type="text/javascript" language="javascript" src="http://www.datatables.net/release-datatables/media/js/jquery.dataTables.js"></script> 
	<script type="text/javascript" charset="utf-8">
		$(document).ready(function() {
			$('#ex1').html ('<table border="1" cellpadding="2" align="center" id="gene" class="display"></table>');
  			$('#gene').dataTable({
				"bProcessing": true,
				"iDisplayLength": 25,
				"bStateSave": true,
				"sPaginationType": "full_numbers",
                <? echo gene_summary_json($data_set, $annotation_set, $type); ?>
			});
			$('#ex2').html ('<table border="1" cellpadding="2" align="center" id="sample" class="display"></table>');
			$('#sample').dataTable({
				"bProcessing": true,
				"iDisplayLength": 25,
				"bStateSave": true,
				"sPaginationType": "full_numbers",
                <? echo sample_summary_json($data_set); ?>
			});
		});
	</script>	
</head>
<body>
	<h1><center>Results: <? echo $data_set; ?></center></h1><br>
	<h3><center>Gene summary based on <? echo $annotation_set; ?> annotation set</center></h3>
	<div id="ex1"></div>
	<br><br>
	<center>
		[<a href="<? echo $vat_config['WEB_DATA_DIR']; ?>/<? echo $data_set; ?>.vcf.gz" target="external">Download compressed VCF file with annotated variants</a>]
		&nbsp;&nbsp;&nbsp;
		[<a href="<? echo $vat_config['WEB_DATA_DIR']; ?>/<? echo $data_set; ?>.geneSummary.txt" target="external">View tab-delimited gene summary file</a>]
	</center>
	<br><br><br><br><br><br>
	<h3><center>Sample summary</center></h3>
	<div id="ex2"></div>
	<br><br>
	<center>
	[<a href="<? echo $vat_config['WEB_DATA_DIR']; ?>/<? echo $data_set; ?>.sampleSummary.txt" target="external">View tab-delimited sample summary file</a>]
	</center>
</body>
    <?
}

/**
 * 
 */
function show_information($data_set, $annotation_set, $gene_id, $type)
{
    global $vat_config;
    
    ?>
 <head>
	<meta charset="utf-8">
	<title>VAT</title>
	<link rel="stylesheet" href="css/style.css" />
</head>
<body>
	<?
	
	$file = $vat_config['WEB_DATA_DIR'].'/'.$data_set.'/'.$gene_id.'.vcf';
	$vcf = new VCF($file);
	
	$vcf_entries = $vcf->parse();
	$groups      = $vcf->get_groups_from_column_headers();
	$vcf_genes   = $vcf->get_gene_summaries($vat_config['WEB_DATA_DIR'].'/'.$annotation_set.'.interval');
	
	$i = 0;
	$curr_vcf_gene = NULL;
	while ($i < count($vcf_genes))
	{
	    $curr_vcf_gene = $vcf_genes[$i];
	    if ($curr_vcf_gene->gene_id == $gene_id)
	        break;
	    $i++;
	}
	if ($i == count($vcf_genes))
	{
	    echo "Unable to find ".$gene_id." in ".$data_set."!";
	    return;
	}
	
	$curr_interval = $curr_vcf_gene->transcripts[0];
	$start = $curr_interval->start;
	$end   = $curr_interval->end;
	for ($i = 1; i < count($curr_vcf_gene->transcripts); $i++)
	{
	    $curr_interval = $curr_vcf_gene->transcripts[$i];
	    if ($start > $curr_interval->start)
	    {
	        $start = $curr_interval->start;
	    }
	    if ($end > $curr_interval->end)
	    {
	        $end = $curr_interval->end;
	    }
	}
	?>
	
	<h1><center><? echo $data_set; ?>: gene summary for <font color=red><? echo $curr_vcf_gene->gene_name; ?></font> [<? echo $gene_id; ?>]</center></h1><br>
	<h3><center>External links:</center></h3>
	<center><b>
        [<a href="http://genome.ucsc.edu/cgi-bin/hgTracks?clade=mammal&org=human&db=hg18&position=%s:%d-%d" target="external">UCSC genome browser</a>]&nbsp;&nbsp;&nbsp;\n",currInterval->chromosome,start - 1000,end + 1000);
        [<a href="http://may2009.archive.ensembl.org/Homo_sapiens/Gene/Summary?g=<? echo $gene_id; ?>" target="external">Ensembl genome browser</a>]&nbsp;&nbsp;&nbsp;
    <? if (strEqual (type,"coding")): ?>
        [<a href="http://www.genecards.org/cgi-bin/carddisp.pl?gene=<? echo $curr_vcf_gene->gene_name; ?>" target="external">Gene Cards</a>]&nbsp;&nbsp;&nbsp;
    <? endif; ?>
    </b></center>
    <br><br><br>

    <h3><center>Transcript summary based on <? echo $annotation_set; ?> annotation set</center></h3>
    <table border="1" cellpadding="2" align="center" width="95%">        
        <thead>
            <tr>
                <th>Transcript name</th>
                <th>Transcript ID</th>
                <th>Chromosome</th>
                <th>Strand</th>
                <th>Start</th>
                <th>End</th>
                <th>Number of exons</th>
                <th>Transcript length</th>
            </tr>
        </thead>
        <tbody>
    <?
    for ($i = 0; $i < count($curr_vcf_gene->transcripts); $i++)
    {
        $curr_interval = $curr_vcf_gene->transcripts[$i];
        list($this_gene_id, 
             $transcript_id, 
             $gene_name, 
             $transcript_name) = VAT::process_transcript_line($curr_interval->name);
        ?>
            <tr align="center">
                <td><? echo $transcript_name; ?></td>
                <td><? echo $transcript_id; ?></td>
                <td><? echo $curr_interval->chromosome; ?></td>
                <td><? echo $curr_interval->strand; ?></td>
                <td><? echo $curr_interval->start; ?></td>
                <td><? echo $curr_interval->end; ?></td>
                <td><? echo $curr_interval->get_num_sub_intervals(); ?></td>
                <td><? echo $curr_interval->get_size(); ?></td>
            </tr>
        <?
    }
    
    ?>
        </tbody>
    </table>
    <br><br>
    <? if ($type == "coding"): ?>
    <h3><center>Graphical representation of genetic variants</center></h3>
    <center><img src="<? echo $vat_config['WEB_DATA_DIR']; ?>/<? echo $data_set; ?>/<? echo $gene_id; ?>.png" /></center>
    <br><br>
    <center><img src="<? echo $vat_config['WEB_DATA_DIR']; ?>/<? echo $data_set; ?>/legend.png" /></center>
    <br><br><br>
    <? elseif ($type == "nonCoding"): ?>
    <h3><center>Graphical representation of the secondary structure</center></h3>
    <center><h4>Reference</center></h4>
    <center><embed src="<? echo $vat_config['WEB_DATA_DIR']; ?>/<? echo $data_set; ?>/<? echo $gene_id; ?>_ref.svg" height="450px" width="1000px" /></center>
    <center><h4>Variants</center></h4>
    <center><embed src="<? echo $vat_config['WEB_DATA_DIR']; ?>/<? echo $data_set; ?>/<? echo $gene_id; ?>_alt.svg" height="450px" width="1000px" /></center>
    <? else: ?>
        <? echo "Invalid type ".$type; ?>
        <? die(); ?>
    <? endif; ?>
    
    <h3><center>Detailed summary of variants</center></h3>
    <table border="1" cellpadding="2" align="center" width="95%">
        <thead>
            <tr>
                <th rowspan="2">Chromosome</th>
                <th rowspan="2">Position</th>
                <th rowspan="2">Reference allele</th>
                <th rowspan="2">Alternate allele</th>
                <th rowspan="2">Identifier</th>
                <th rowspan="2">Type</th>
                <th rowspan="2">Fraction of transcripts affected</th>
                <th rowspan="2">Transcripts</th>
                <th rowspan="2">Transcript details</th>
    <?
    $i = 0;
    while ($i < count($curr_vcf_gene->vcf_entries))
    {
        $curr_vcf_entry = $curr_vcf_gene->vcf_entries[$i];
        if (count($curr_vcf_entry->genotypes) > 0)
            break;
        $i++;
    }
    ?>
    
    <? if ($i < count($curr_vcf_gene->vcf_entries)): ?>
                <th colspan="<? echo count($groups); ?>">Alternate allele frequencies</th>
                <th rowspan="2">Genotypes</th>");
            </tr>");
            <tr>
        <? for ($i = 0; $i < count($groups); $i++): ?>
                <th><? echo $groups[$i]; ?></th>
        <? endfor; ?>
    <? endif; ?>
            </tr>
        </thead>
        <tbody>
    <?
    for ($i = 0; $i < count($curr_vcf_gene->vcf_entries); $i++)
    {
        $curr_vcf_entry = $curr_vcf_gene->vcf_entries[i];
        if ($curr_vcf_entry->has_multiple_alternative_alleles())
            continue;
        
        for ($j = 0; $j < count($curr_vcf_entry->annotations); $j++)
        {
            $curr_vcf_annotation = $curr_vcf_entry->annotations[$j];
            if ($curr_vcf_gene->gene_id == $curr_vcf_annotation->gene_id)
            {
                ?>
            <tr align="center">
                <td><? echo $curr_vcf_entry->chromosome; ?></td>
                <td><? echo $curr_vcf_entry->position; ?></td>
                <td><? echo strlen($curr_vcf_entry->reference_allele) > 50 ? "Length > 50 nucleotides" : $curr_vcf_entry->reference_allele; ?></td>
                <td><? echo strlen($curr_vcf_entry->alternate_allele) > 50 ? "Length > 50 nucleotides" : $curr_vcf_entry->alternate_allele; ?></td> 
                <td><? echo hyperlink_id($curr_vcf_entry->id); ?></td>
                <td><? echo $curr_vcf_annotation->type; ?></td>
                <td><? echo $curr_vcf_annotation->fraction; ?></td>
                <td>
                <?
                for ($k = 0; $k < count($curr_vcf_annotation->transcript_ids); $k++)
                {
                    echo $curr_vcf_annotation->transcript_ids[$k]
                        .($k < count($curr_vcf_annotation->transcript_ids) - 1) ? "<br>" : "";
                }
                ?>
                </td>
                <td>
                <?
                for ($k = 0; $k < count($curr_vcf_annotation->transcript_details); $k++)
                {
                    echo $curr_vcf_annotation->transcript_details[$k]
                        .($k < count($curr_vcf_annotation->transcript_details) - 1) ? "<br>" : "";
                }
                ?>
                </td>
                <?
                for ($k = 0; $k < count($groups); $k++)
                {
                    ?>
                <td>
                    <?
                    list($allele_count0, $total_allele_count0) = $curr_vcf_entry->get_allele_information($groups[$k], 0);
                    list($allele_count1, $total_allele_count1) = $curr_vcf_entry->get_allele_information($groups[$k], 1);
                    
                    if ($allele_count0 == 0 && $allele_count1 == 0)
                    {
                        echo "N/A\n";
                    }
                    else 
                    {
                        printf("%3f\n", $allele_count1 / $total_allele_count1);
                    }
                    ?>
                </td>
                    <?
                }
                ?>
                <td>
                    <a href="vat.php?mode=showGenotypes&dataSet=<? echo $data_set; ?>&geneId=<? echo $gene_id; ?>&index=<? echo $i; ?>" target="genotypes">Link</a>
                </td>
            </tr>
                <?
            }
        }
    }
    ?>      
        </tbody>
    </table>
</body>
    <?
}

/**
 * 
 */
function show_gene_information($data_set, $annotation_set, $gene_id)
{
    show_information($data_set, $annotation_set, $gene_id, "coding");
}

/**
 * 
 */
function show_non_coding_information($data_set, $annotation_set, $gene_id)
{
    show_information($data_set, $annotation_set, $gene_id, "nonCoding");
}

/**
 * 
 */
function show_genotypes($data_set, $annotation_set, $index)
{
    global $vat_config;
    
    $path = $vat_config['WEB_DATA_DIR'].'/'.$data_set.'/'.$gene_id.'.vcf';
    $vcf = new VCF($path);
    $vcf_entries = $vcf-parse();
    $groups = $vcf->get_groups_from_column_header;
    
    if (index >= count($vcf_entries))
    {
        echo "Invalid index";
        return FALSE;
    }
    
    $curr_vcf_entry = $vcf_entries[$index];
    ?>
 <head>
    <meta charset="utf-8">
    <title>VAT</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
    <h3><center>Variant summary</center></h3>
    <table border="1" cellpadding="2" align="center" width="95%">
        <thead>
            <tr>
                <th rowspan="2">Chromosome</th>
                <th rowspan="2">Position</th>
                <th rowspan="2">Reference allele</th>
                <th rowspan="2">Alternate allele</th>
            </tr>
        </thead>
        <tbody> 
            <tr align="center">
                <td><? echo $curr_vcf_entry->chromosome; ?></td>
                <td><? echo $curr_vcf_entry->position; ?></td>
                <td><? echo $curr_vcf_entry->referenceAllele; ?></td>
                <td><? echo $curr_vcf_entry->alternateAllele; ?></td>
            </tr>
        </tbody>
    </table>
    <br><br>

    <h3><center>Genotype information</center></h3>
    <table border="1" cellpadding="2" align="center" width="95%">
        <thead>
            <tr>
    <? for ($i = 0; $i < count($groups); $i++): ?>
                <th><? echo $groups[$i]; ?></th>
    <? endfor; ?>
            </tr>
        </thead>
        <tbody> 
            <tr align="center">
    <? for ($i = 0; $i < count($groups); $i++): ?>
                <td>
        <?
        list($allele_count0, $total_allele_count0) = $curr_vcf_entry->get_allele_information($groups[$i], 0);
        list($allele_count1, $total_allele_count1) = $curr_vcf_entry->get_allele_information($groups[$i], 1);
        
        if ($allele_count0 == 0 && $allele_count1 == 0)
        {
            echo "RefCount = $total_allele_count0<br />\n";
            echo "AltCount = 0\n";
        }
        else 
        {
            echo "RefCount = $allele_count0<br />\n";
            echo "AltCount = $allele_count1\n";
        }
        ?>
                </td>
    <? endfor; ?>
            </tr>
            <tr align="center" valign="top">
    <? for ($i = 0; $i < count($groups); $i++): ?>
                <td>
        <? foreach ($curr_vcf_entry->genotypes as $curr_vcf_genotype): ?>
            <? if ($groups[$i] == $curr_vcf_genotype->group): ?>
                    <? echo $curr_vcf_genotype->sample; ?>: <? echo $curr_vcf_genotype->genotype; ?>
            <? endif; ?>
        <? endforeach; ?>
                </td>
    <? endfor; ?>
            </tr>
        </tbody>
    </table>
</body>
    <?
}

/**
 * Controller/view for the clean up data page
 */
function clean_up_data()
{
    global $vat_config;
    
    ?>
<head>
	<title>VAT</title>
	<link rel="stylesheet" href="css/style.css" />
</head>
<body>
	<? $cmd = sprintf("rm -rf %s/vat.*", $vat_config['WEB_DATA_DIR']); ?>
	<? system($cmd); ?>
	Done deleting temporary files...
</body>
    <?   
}

/**
 * The "main" section
 */

?>

<!DOCTYPE head PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<?
if (!isset($_GET['mode']))
    die('mode argument not set');

$data_set       = $_GET['dataSet'];
$annotation_set = $_GET['annotationSet'];
$gene_id        = $_GET['geneId'];
$type           = $_GET['type'];
$index          = $_GET['index'];

switch ($_GET['mode'])
{
    case 'process':
        process_data($data_set, $annotation_set, $type);
        break;
    case 'showGene':
        show_gene_information($data_set, $annotation_set, $gene_id);
        break;
    case 'showNonCoding':
        show_non_coding_information($data_set, $annotation_set, $gene_id);
        break;
    case 'showGenotypes':
        show_genotypes($data_set, $annotation_set, $index);
        break;
    case 'cleanUp':
        clean_up_data();
        break;
    default:
        die('Invalid mode');
        break;
}

?>

</html>