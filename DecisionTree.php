<?php

require_once "models/autoload.php";


class DecisionTree
{

    /**
     *
     * initialize elements and attributes
     *
     * @param $elements
     * @param $attributes
     */
    public function __construct($elements = [], $attributes = [])
    {
        if (empty($elements))
            $this->elements = [
                "dado" =>
                    [["taglia" => "giallo", "forma" => "compatta", "buchi" => 1],
                        ["taglia" => "small", "forma" => "compatta", "buchi" => 1],
                        ["taglia" => "small", "forma" => "compatta", "buchi" => 1]],
                "bullone" =>
                    [["taglia" => "small", "forma" => "compatta", "buchi" => 0],
                        ["taglia" => "small", "forma" => "oblunga", "buchi" => 1]],
                "chiave" =>
                    [["taglia" => "small", "forma" => "altra", "buchi" => 2],
                        ["taglia" => "small", "forma" => "oblunga", "buchi" => 1],
                        ["taglia" => "large", "forma" => "oblunga", "buchi" => 1]],
                "penna" =>
                    [["taglia" => "large", "forma" => "oblunga", "buchi" => 0],
                        ["taglia" => "large", "forma" => "oblunga", "buchi" => 0]],
                "forbici" =>
                    [["taglia" => "large", "forma" => "oblunga", "buchi" => 2],
                        ["taglia" => "large", "forma" => "altra", "buchi" => 2]]];
        else
            $this->elements = $elements;

        if (empty($attributes))
            $this->attributes = ["taglia" => ["small", "large"],
                "forma" => ["oblunga", "compatta", "altra"],
                "buchi" => [0, 1, 2, 3, "molti"]];
        else
            $this->attributes = $attributes;
    }


    /***
     *
     * finds all the elements and the attributes to use to classify
     *
     * @return string
     */
    public function induces_tree()
    {
        $elements = $this->findall("elements");
        $attributes = $this->findall("attributes");
        $trees = $this->start_induces_tree($attributes, $elements);
        return $this->convertToString($trees);
    }

    /**
     * can be overriden
     *
     * needs to find elements and attributes in the requested structure
     *
     * @param $type
     * @return mixed
     */
    protected function findall($type)
    {
        return $this->$type;
    }

    /***
     *
     * finds the best discriminatory attribute
     * returns the corresponding tree
     *
     * @param $attributes
     * @param $elements
     * @return array
     */
    private function start_induces_tree($attributes, $elements)
    {
        if (empty($elements))
            return [];

        $sameElements = $this->sameElements($elements);
        if ($sameElements)
            return ["l" => [$sameElements]];

        $attribute = $this->chooseAttribute($attributes, $elements);
        if ($attribute !== false) {
            $key = array_keys($attribute)[0];
            $values = $attribute[$key];
            $this->del($key, $attributes);
            $SAlberi = ($this->induces_trees($key, $values, $attributes, $elements));
            return ["t" => [$key => $SAlberi]];
        }

        return ["l" => $this->retrieveKeys($elements)];
    }

    /***
     *
     * filters elements that are a superset of the attribute elements
     *
     * ex. ["shape"=>"something", "number"=>2, "color"=>"orange"] is a superset of ["shape"=>"something"], "number"=>2]
     *
     * @param $elements
     * @param $attributes
     * @return array
     */
    protected function attval_subset($elements, $attributes)
    {
        $array = [];
        foreach ($elements as $class => $values) {
            foreach ($values as $value) {
                if ($this->contains($value, $attributes)) {
                    if (!isset($array[$class]))
                        $array[$class] = [];
                    $array[$class][] = $value;
                }
            }

        }
        return $array;
    }

    /**
     * @param $object
     * @param $attributes
     * @return bool
     */
    private function contains($object, $attributes)
    {
        foreach ($attributes as $attr => $value)
            if (!key_exists($attr, $object) or $object[$attr] != $value)
                return false;

        return true;
    }

    /**
     *
     * if the array contains one key, will return that key
     *
     * @param array $elements
     * @return false|string
     */
    private function sameElements(array $elements)
    {
        if (sizeof($elements) === 1)
            return array_keys($elements)[0];

        return false;
    }


    /**
     *
     * choose the best attribute to use to classify
     * uses Gini coefficient => see https://en.wikipedia.org/wiki/Gini_coefficient
     *
     * @param array $attributes
     * @param array $elements
     * @return false
     */
    private function chooseAttribute(array $attributes, array $elements)
    {
        $gini = new TreeStruct();

        foreach ($attributes as $attr => $values) {
            $A = [$attr => $values];
            $value = $this->inequality($elements, $A);
            $gini->addNode($value, ["A" => $A]);
        }

        if ($gini->tree === null)
            return false;

        return $gini->getMin()->A;
    }


    /**
     * returns the gini coefficient for the specific attribute
     *
     * @param array $elements
     * @param array $attr
     * @return float
     */
    private function inequality(array $elements, array $attr)
    {
        foreach ($attr as $key => $value)
            return $this->weighted_sum($elements, $key, $value, 0);
    }

    /**
     *
     * computes the gini coefficient
     *
     * @param $elements
     * @param $attribute
     * @param $attVals
     * @param $sum
     * @return float
     */
    protected function weighted_sum($elements, $attribute, $attVals, $sum)
    {
        if (empty($attVals))
            return $sum;

        $val = array_shift($attVals);
        $N = $this->countElements($elements);
        $exElements = $this->attval_subset($elements, [$attribute => $val]);
        $NVal = $this->countElements($exElements);

        if (empty($NVal))
            return $this->weighted_sum($elements, $attribute, $attVals, $sum);

        $ClDst = array();

        foreach ($exElements as $class => $value) {
            $ClDst[] = sizeof($value) / $NVal;
        }

        $gini = $this->gini($ClDst);
        $sum += $gini * $NVal / $N;

        return $this->weighted_sum($elements, $attribute, $attVals, $sum);
    }

    /**
     *
     * counts the total elements
     *
     * @param $elements
     * @return int
     */
    private function countElements($elements)
    {
        $sum = 0;
        foreach ($elements as $values) {
            $sum += sizeof($values);
        }
        return $sum;
    }

    /**
     * @param array $ClDst
     * @return float
     */
    private function gini(array $ClDst)
    {
        return 1 - $this->sum_squares($ClDst, 0);
    }

    /**
     * @param array $ClDst
     * @param $sum
     * @return float
     */
    private function sum_squares(array $ClDst, $sum)
    {
        if (empty($ClDst))
            return $sum;

        $val = array_shift($ClDst);

        $sum += $val * $val;
        return $this->sum_squares($ClDst, $sum);
    }

    /**
     * @param $attribute
     * @param $attributes
     * @return void
     */
    private function del($attribute, &$attributes)
    {
        unset($attributes[$attribute]);
    }

    /**
     *
     * for each attribute found, generates the subtrees
     *
     * @param $key
     * @param $values
     * @param $attributes
     * @param $elements
     * @return array
     */
    private function induces_trees($key, $values, $attributes, $elements)
    {
        if (empty($values))
            return [];

        $val = array_shift($values);
        $subsetElements = $this->attval_subset($elements, [$key => $val]);
        $Tree1 = $this->start_induces_tree($attributes, $subsetElements);
        $Trees = $this->induces_trees($key, $values, $attributes, $elements);

        $return = ["b" => ["t" => [$val => $Tree1]]];

        if (!empty($Trees))
            $return["b"]["o"] = $Trees;

        return $return;
    }

    /**
     * @param $elements
     * @return array
     */
    private function retrieveKeys($elements)
    {
        return array_keys($elements);
    }

    /**
     * @param array $trees
     * @return string
     */
    private function convertToString(array $trees)
    {
        $str = "";
        foreach ($trees as $key => $value) {
            if ($key === "l")
                $str .= " [" . implode(",", $value) . "]";
            elseif ($key === "t") {
                $key = array_keys($value)[0];
                $str .= "$key => [" . $this->convertToString($value[$key]) . "]";
            } elseif ($key === "b") {
                $str .= $this->convertToString($value);
            } elseif ($key === "o") {
                $str .= " | " . $this->convertToString($value);
            }
        }

        return $str;
    }

}

$decisionTree = new DecisionTree();
$start_time = microtime(true);
echo $decisionTree->induces_tree();
$end_time = microtime(true);
$execution_time = ($end_time - $start_time);
echo "<br><br> Execution time of script = " . $execution_time . " sec";
