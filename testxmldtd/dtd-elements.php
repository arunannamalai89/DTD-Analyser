<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

function is_assoc(array $array) {
    // Keys of the array
    $keys = array_keys($array);

    // If the array keys of the keys match the keys, then the array must
    // not be associative (e.g. the keys array looked like {0:0, 1:1...}).
    return array_keys($keys) !== $keys;
}

function typeAttributeToArrayOrString($type) {
    if (substr($type, 0, 1) === '(' && substr($type, -1) === ')') {
        return preg_split('/\|/', substr($type, 1, -1));
    }
    return $type;
}

function xmlToArray($xml, $options = array()) {
    $defaults = array(
        'namespaceSeparator' => ':',//you may want this to be something other than a colon
        'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
        'alwaysArray' => array(),   //array of xml tag names which should always become arrays
        'autoArray' => true,        //only create arrays for tags which appear more than once
        'textContent' => '$',       //key used for the text content of elements
        'autoText' => true,         //skip textContent key if node has no attributes or child nodes
        'keySearch' => false,       //optional search and replace on tag and attribute names
        'keyReplace' => false       //replace values for above search values (as passed to str_replace())
    );
    $options = array_merge($defaults, $options);
    $namespaces = $xml->getDocNamespaces();
    $namespaces[''] = null; //add base (empty) namespace

    //get attributes from all namespaces
    $attributesArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
            //replace characters in attribute name
            if ($options['keySearch']) $attributeName =
                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
            $attributeKey = $options['attributePrefix']
                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                    . $attributeName;
            $attributesArray[$attributeKey] = (string)$attribute;
        }
    }

    //get child nodes from all namespaces
    $tagsArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->children($namespace) as $childXml) {
            //recurse into child nodes
            $childArray = xmlToArray($childXml, $options);
            list($childTagName, $childProperties) = each($childArray);

            //replace characters in tag name
            if ($options['keySearch']) $childTagName =
                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
            //add namespace prefix, if any
            if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

            if (!isset($tagsArray[$childTagName])) {
                //only entry with this key
                //test if tags of this type should always be arrays, no matter the element count
                $tagsArray[$childTagName] =
                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                        ? array($childProperties) : $childProperties;
            } elseif (
                is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                === range(0, count($tagsArray[$childTagName]) - 1)
            ) {
                //key already exists and is integer indexed array
                $tagsArray[$childTagName][] = $childProperties;
            } else {
                //key exists so convert to integer indexed array with previous value in position 0
                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
            }
        }
    }

    //get text content of node
    $textContentArray = array();
    $plainText = trim((string)$xml);
    if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

    //stick it all together
    $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

    //return node as array
    return array(
        $xml->getName() => $propertiesArray
    );
}

//
$blockLevelElements = ['p'];

$inlineElementsList = ['addr-line', 'city', 'country', 'fax', 'institution', 'institution-wrap', 'phone', 'postal-code', 'state', 'email', 'ext-link', 'uri', 'related-article', 'related-object', 'break', 'bold', 'fixed-case', 'italic', 'monospace', 'overline', 'roman', 'sans-serif', 'sc', 'strike', 'underline', 'ruby', 'fn', 'target', 'xref', 'alternatives', 'inline-graphic', 'private-char', 'inline-formula', 'abbrev', 'milestone-end', 'milestone-start', 'named-content', 'styled-content', 'surname', 'given-names', 'suffix', 'article-title', 'publisher-name', 'publisher-loc', 'sub', 'sup', 'label', 'aff', 'tex-math', 'array', 'code', 'graphic', 'inline-supplementary-material', 'preformat', 'prefix', 'supplementary-material', 'table', 'textual-form', 'speech', 'statement', 'verse-group', 'alt-text', 'long-desc', 'author-notes', 'pub-date', 'volume', 'volume-id', 'volume-series', 'issue', 'issue-id', 'issue-title', 'issue-sponsor', 'issue-part', 'volume-issue-group', 'isbn', 'supplement', 'fpage', 'lpage', 'page-range', 'elocation-id', 'product', 'history', 'permissions', 'self-uri', 'trans-abstract', 'funding-group', 'conference', 'counts', 'custom-meta-group', 'funding-source', 'award-id', 'principal-award-recipient', 'principal-investigator', 'subj-group', 'series-title', 'series-text', 'sig-block', 'caption', 'compound-kwd-part', 'compound-subject-part', 'conf-date', 'conf-name', 'conf-acronym', 'conf-num', 'conf-loc', 'conf-sponsor', 'conf-theme', 'address', 'aff-alternatives', 'author-comment', 'bio', 'on-behalf-of', 'role', 'anonymous', 'collab', 'collab-alternatives', 'name-alternatives', 'degrees', 'count', 'fig-count', 'table-count', 'equation-count', 'ref-count', 'page-count', 'word-count', 'meta-name', 'meta-value', 'custom-meta', 'day', 'month', 'season', 'year', 'era', 'term', 'term-head', 'kwd-group', 'chem-struct-wrap', 'fig', 'fig-group', 'disp-formula-group', 'etal', 'notes', 'article-id', 'article-categories', 'volume-series', 'elocation-id', 'award-group', 'funding-statement', 'open-access', 'abstract', 'object-id', 'attrib', 'date', 'institution-id', 'journal-id', 'issn', 'issn-l', 'publisher', 'compound-kwd', 'nested-kwd', 'license-p', 'string-name', 'sec-meta', 'copyright-statement', 'copyright-year', 'copyright-holder', 'glyph-data', 'glyph-ref', 'rb', 'rt', 'speaker', 'trans-title', 'trans-subtitle', 'verse-line', 'string-date', 'annotation', 'comment', 'data-title', 'date-in-citation', 'edition', 'gov', 'name', 'part-title', 'patent', 'object-id', 'person-group', 'pub-id', 'series', 'size', 'source', 'std', 'trans-source', 'version', 'contrib-group', 'table-wrap', 'price', 'contrib', 'corresp', 'inline-formula'];

$blockLevelInlineElements = ['subj-group', 'contrib-group', 'author-notes', 'pub-date', 'abstract', 'kwd-group', 'fig', 'person-group', 'string-name'];

$readOnlyTagsList = ['journal-meta'];

$oneLinerTagsList = ['journal-id', 'journal-title', 'abbrev-journal-title', 'issn', 'publisher-name', 'article-id', 'article-title', 'alt-title', 'title', 'subject', 'volume', 'issue', 'fpage', 'lpage', 'self-uri', 'kwd', 'contrib-id', 'sup', 'sc', 'institution', 'country', 'label', 'graphic', 'mml:mi', 'mml:mo', 'mml:mn', 'mml:mtext', 'day', 'month', 'year', 'source', 'supplement', 'publisher-loc', 'surname', 'given-names', 'etal', 'comment'];

$collapsibleOffTagsList = ['article', 'journal-id', 'journal-title', 'abbrev-journal-title', 'issn', 'publisher-name', 'title', 'article-id', 'article-title', 'contrib', 'corresp', 'kwd', 'alt-title', 'contrib-id', 'disp-formula', 'mml:mrow', 'mml:mi', 'mml:mo', 'mml:mn', 'mml:mtext'];

$collapsedTagsList = [];
$editRawXMlTagList = ['p', 'ref-list'];
$collapsibleOnTagList = ['person-group'];

$cloneTagList = [];
$cloneTagList['ref'] = [
    'label' => 'label',
    'element-citation' => [
        'person-group' => [
            'string-name' => [
                'surname' => 'surname',
                'given-names' => 'given-names'
            ]
        ],
        'year' => 'year',
        'article-title' => 'article-title',
        'source' => 'source',
        'volume' => 'volume',
        'issue' => 'issue',
        'fpage' => 'fpage',
        'lpage' => 'lpage'
    ]
];

$cloneTagList['fn'] = [
    'p' => 'p'
];

$xmlNode = simplexml_load_file('data-anlaysed.xml');
$arrayData = xmlToArray($xmlNode);
unset($arrayData['declarations']['dtd']);
unset($arrayData['declarations']['parameterEntities']);

$elements = &$arrayData['declarations']['elements']['element'];

$newElements = [];
foreach ($elements as &$element) {
    unset($element['@dtdOrder']);
    unset($element['declaredIn']);
    unset($element['content-model']['@minified']);
    unset($element['content-model']['@spaced']);
    $name = $element['@name'];
    unset($element['@name']);

    if (array_key_exists('context', $element) && array_key_exists('parent', $element['context'])) {
        $parents = $element['context']['parent'];

        $newChilds = [
            'caption' => $name,
            'action' => 'Xonomy.newElementChild',
            'insertBeforeAction' => 'Xonomy.newElementChildFirst',
            'actionParameter' => "<$name/>"
        ];

        if(array_key_exists($name, $cloneTagList) === true) {
            $newChilds['action'] = 'Xonomy.newElementCloneChild';
            $newChilds['insertBeforeAction'] = 'Xonomy.newElementCloneFirst';
        }
        
        $inlineMenus = [
            'caption' => $name,
            'action' => 'Xonomy.customWrap',
            'actionParameter' => [
                'template' => "<$name>$</$name>",
                'placeholder' => "$"
            ]
        ];

        if (is_assoc($parents)) {
            $newElements[$parents['@name']]['menu'][] = $newChilds;
            $newElements[$parents['@name']]['inlineMenu'][] = $inlineMenus;
            $newElements[$parents['@name']]['possibleChilds'][] = $name;
        }
        else {
            foreach ($parents as $parent) {
                if (array_key_exists('@name', $parent)) {
                    $newElements[$parent['@name']]['menu'][] = $newChilds;
                    $newElements[$parent['@name']]['inlineMenu'][] = $inlineMenus;
                    $newElements[$parent['@name']]['possibleChilds'][] = $name;
                }
            }
        }

        unset($element['context']);
    }
}
unset($elements['element']);
$arrayData['declarations']['elements'] = $newElements;
unset($arrayData['declarations']['generalEntities']);

// echo "\n<pre>"; print_r($arrayData); echo "</pre>\n"; exit;

function getAttrMenuSpecList($attName, $declaration) {
    $attrPickList = [];
    $attrTypeList = typeAttributeToArrayOrString($declaration['@type']);
    if(is_array($attrTypeList) === true) {
        $askPickListArr = [];
        foreach ($attrTypeList as $attrType) {
            array_push(
                $askPickListArr,
                ['value' => $attrType, 'caption' => $attrType]
            );
        }
        $attrPickList['asker'] = 'Xonomy.askPicklist';
        $attrPickList['askerParameter'] = $askPickListArr;
    }
    else {
        /*if($declaration['@type'] == 'IDREFS') {
            $attrPickList['asker'] = 'Xonomy.askAutoIDPicklist';
        }
        else {*/
            $attrPickList['asker'] = 'Xonomy.askString';
        // }
    }

    $attrPickList['menu'][] = [
        'caption' => "Delete @$attName attribute",
        'action' => 'Xonomy.deleteAttribute'
    ];

    return $attrPickList;
}

$elements = &$arrayData['declarations']['elements'];
$attributes = &$arrayData['declarations']['attributes']['attribute'];
foreach ($attributes as &$attribute) {
    $element = '';
    $attName = $attribute['@name'];
    $declarations = &$attribute['attributeDeclaration'];
    $attrsListActions = [
        'caption' => $attName,
        'action' => 'Xonomy.newAttribute',
        'actionParameter' => ['name' => $attName, 'value' => '']
    ];

    $attributesAction = [
        'caption' => $attName,
        'action' => 'Xonomy.newAttribute',
        'actionParameter' => ['name' => $attName, 'value' => '']
    ];

    if (is_assoc($declarations)) {
        $element = $declarations['@element'];
        $attrPickList = getAttrMenuSpecList($attName, $declarations);

        $elements[$element]['attributes'][$attName] = $attrPickList;
        $elements[$element]['possibleAttrs'][] =  $attName;
        $elements[$element]['attributesMenu'][] =  $attrsListActions;
        unset($declarations['declaredIn']);
    }
    else {
        foreach ($declarations as &$declaration) {
            $element = $declaration['@element'];
            $attrPickList = getAttrMenuSpecList($attName, $declaration);
            
            $elements[$element]['attributes'][$attName] = $attrPickList;
            $elements[$element]['possibleAttrs'][] =  $attName;
            $elements[$element]['attributesMenu'][] =  $attrsListActions;
            unset($declaration['declaredIn']);
        }
    }
}

$attrMainAction = [
    'caption'=>'Insert Attributes',
    'insertOn' => 'default',
    'actionParameter'=>'listAttr'
];
$elemFirstMainAction = [
    'caption'=>'Insert child element first',
    'insertOn' => 'first',
    'actionParameter'=>'listTag'
];
$elemMainAction = [
    'caption'=>'Insert child element last',
    'insertOn' => 'default',
    'actionParameter'=>'listTag'
];
$editActions = ['caption' => 'Edit as XML', 'action' => 'Xonomy.editRaw'];
$unwrapActions = ['caption' => 'Unwrap', 'action' => 'Xonomy.unwrap'];
$deleteActions = ['caption' => 'Delete', 'action' => 'Xonomy.deleteElement'];
$commentMenu = [
    'caption' => '<!-- Comment -->',
    'action' => 'Xonomy.customWrap',
    'actionParameter' => [
        'template' => "<xml-comment>$</xml-comment>",
        'placeholder' => "$"
    ]
];

$processInsMenu = [
    'caption' => '<? Processing Instruction ?>',
    'action' => 'Xonomy.customWrap',
    'actionParameter' => [
        'template' => "<process-ins>$</process-ins>",
        'placeholder' => "$"
    ]
];

$changeProcessInsTagName = [
    "caption" => "Change Tag Name",
    "action" => "Xonomy.replaceProcessInsTag",
    "actionParameter" => "changeProcessIns"
];

$mainCommentAction = [
    "caption" =>"Insert <!-- Comment -->",
    "action" =>"Xonomy.newElementBefore",
    "actionParameter" =>"<xml-comment/>"
];

$mainPIActions = [
    "caption" => "Insert <? Processing Instruction ?>",
    "action" => "Xonomy.newElementBefore",
    "actionParameter" => "<process-ins/>"
];

$newCommentChilds = [
    'caption' => '<!-- Comment -->',
    'action' => 'Xonomy.newElementChild',
    'insertBeforeAction' => 'Xonomy.newElementChildFirst',
    'actionParameter' => "<xml-comment/>"
];

$newPIChilds = [
    'caption' => '<!-- Processing Instruction -->',
    'action' => 'Xonomy.newElementChild',
    'insertBeforeAction' => 'Xonomy.newElementChildFirst',
    'actionParameter' => "<process-ins/>"
];

$elements = &$arrayData['declarations']['elements'];
foreach ($elements as $element => $elemData) {
    
    if(
        array_key_exists('possibleAttrs', $elemData) === true &&
        count($elemData['possibleAttrs']) > 0
    ) {
        $elements[$element]['mainActionMenu'][] = $attrMainAction;
    }

    if(
        array_key_exists('possibleChilds', $elemData) === true &&
        count($elemData['possibleChilds']) > 0
    ) {
        $elements[$element]['mainActionMenu'][] = $elemFirstMainAction;
        $elements[$element]['mainActionMenu'][] = $elemMainAction;
    }

    if(array_key_exists('inlineMenu', $elements[$element]) === true) {
        $elements[$element]['inlineMenu'][] = $commentMenu;
        $elements[$element]['inlineMenu'][] = $processInsMenu;
    }
    
    if(array_key_exists($element, $cloneTagList) === true) {
        $cloneActionsBefore = [
            'caption' => "Insert <$element> Structure Before",
            'action' => 'Xonomy.newElementCloneBefore',
            'actionParameter' => "<$element/>"
        ];
        $cloneActionsAfter = [
            'caption' => "Insert <$element> Structure After",
            'action' => 'Xonomy.newElementCloneAfter',
            'actionParameter' => "<$element/>"
        ];
        $elements[$element]['mainActionMenu'][] = $cloneActionsBefore;
        $elements[$element]['mainActionMenu'][] = $cloneActionsAfter;
        $elements[$element]['cloneMenu'] = $cloneTagList[$element];
    }
    else {
        $elemMainActionBefore = [
            'caption'=>"Insert <$element> Tag Before",
            'action' => 'Xonomy.newElementBefore',
            'actionParameter' => "<$element/>"
        ];
        $elements[$element]['mainActionMenu'][] = $elemMainActionBefore;

        $elemMainActionAfter = [
            'caption'=>"Insert <$element> Tag After",
            'action' => 'Xonomy.newElementAfter',
            'actionParameter' => "<$element/>"
        ];
        $elements[$element]['mainActionMenu'][] = $elemMainActionAfter;
    }

    $elements[$element]['mainActionMenu'][] = $editActions;
    $elements[$element]['mainActionMenu'][] = $mainCommentAction;
    $elements[$element]['mainActionMenu'][] = $mainPIActions;
    $elements[$element]['collapsible'] = true;
    if(array_search($element, $readOnlyTagsList) !== false) {
        $elements[$element]['isReadOnly'] = true;
    }

    if(array_search($element, $oneLinerTagsList) !== false) {
        $elements[$element]['oneliner'] = true;
    }

    if(array_search($element, $collapsibleOffTagsList) !== false) {
        $elements[$element]['collapsible'] = false;
    }

    if(array_search($element, $collapsedTagsList) !== false) {
        $elements[$element]['collapsed'] = true;
    }

    if(array_search($element, $inlineElementsList) !== false) {
        $elements[$element]['hasText'] = true;
        $elements[$element]['collapsible'] = false;
    }

    if(array_search($element, $blockLevelElements) !== false) {
        $elements[$element]['hasText'] = true;
        $elements[$element]['collapsible'] = false;
    }

    if(array_search($element, $blockLevelInlineElements) !== false) {
        $elements[$element]['hasText'] = false;
        $elements[$element]['collapsible'] = false;
    }

    $elements[$element]['mainActionMenu'][] = $unwrapActions;
    $elements[$element]['mainActionMenu'][] = $deleteActions;

    $elements[$element]['menu'][] = $newCommentChilds;
    $elements[$element]['menu'][] = $newPIChilds;
}

$elements['opt_INS']['hasText'] = true;
$elements['opt_INS']['collapsible'] = false;
$elements['opt_INS']['mainActionMenu'][] = $unwrapActions;
$elements['opt_INS']['mainActionMenu'][] = $deleteActions;

$elements['opt_DEL']['hasText'] = true;
$elements['opt_DEL']['collapsible'] = false;
$elements['opt_DEL']['mainActionMenu'][] = $unwrapActions;
$elements['opt_DEL']['mainActionMenu'][] = $deleteActions;

$elements['opt_COMMENT']['hasText'] = true;
$elements['opt_COMMENT']['collapsible'] = false;
$elements['opt_COMMENT']['oneliner'] = true;
$elements['opt_COMMENT']['mainActionMenu'][] = $deleteActions;

$elements['xml-comment']['hasText'] = true;
$elements['xml-comment']['collapsible'] = false;
$elements['xml-comment']['oneliner'] = true;
$elements['xml-comment']['isReadOnly'] = false;
$elements['xml-comment']['mainActionMenu'][] = $unwrapActions;
$elements['xml-comment']['mainActionMenu'][] = $deleteActions;

$elements['process-ins']['hasText'] = true;
$elements['process-ins']['collapsible'] = false;
$elements['process-ins']['oneliner'] = true;
$elements['process-ins']['isReadOnly'] = false;
$elements['process-ins']['mainActionMenu'][] = $changeProcessInsTagName;
$elements['process-ins']['mainActionMenu'][] = $unwrapActions;
$elements['process-ins']['mainActionMenu'][] = $deleteActions;

unset($arrayData['declarations']['attributes']);
file_put_contents('JATS-journalpublishing1-pretty.json', json_encode($arrayData['declarations'], JSON_PRETTY_PRINT));
file_put_contents('JATS-journalpublishing1.json', json_encode($arrayData['declarations']));
