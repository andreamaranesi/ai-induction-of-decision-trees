<?php

class TreeStruct
{

    /**
     * the tree structure
     *
     * @var null|stdClass
     */
    public $tree = null;


    /**
     * returns the max Node
     *
     * @param $tree
     * @return false|stdClass
     */
    public function getMax($tree = false)
    {
        if ($tree === false)
            $tree = $this->tree;
        if ($tree === null)
            return false;
        if ($tree->right != null)
            return $this->getMax($tree->right);
        return $tree;
    }

    /**
     * returns the min Node
     *
     * @param $tree
     * @return false|stdClass
     */
    public function getMin($tree = false)
    {
        if ($tree === false)
            $tree = $this->tree;
        if ($tree === null)
            return false;
        if ($tree->left != null)
            return $this->getMin($tree->left);

        return $tree;
    }

    public function display($tree = false)
    {
        $string = "";
        if ($tree === false)
            $tree = $this->tree;
        if ($tree !== null) {
            $string .= $this->display($tree->left);
            $string .= $tree->value . " ";
            $string .= $this->display($tree->right);
            return $string;
        } else {
            return "";
        }
    }

    /**
     * generates a node
     *
     * @param $data
     * @param $extraData
     * @return stdClass
     */
    private function addLeaf($data, $extraData = [])
    {
        $node = new stdClass();

        if (!empty($extraData))
            foreach ($extraData as $attr => $value)
                $node->$attr = $value;

        $node->left = null;
        $node->right = null;
        $node->value = $data;

        return $node;
    }

    /**
     * adds a node
     *
     * @param $data
     * @param $extraData
     * @param $tree
     * @return void
     */
    public function addNode($data, $extraData = [], &$tree = false)
    {
        if ($tree === false)
            $tree = &$this->tree;
        if ($tree === null) {
            $tree = $this->addLeaf($data, $extraData);
        } else {
            if ($tree->value > $data)
                $this->addNode($data, $extraData, $tree->left);
            else
                $this->addNode($data, $extraData, $tree->right);
        }
    }

}
