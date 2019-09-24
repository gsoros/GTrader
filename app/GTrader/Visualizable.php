<?php

namespace GTrader;

trait Visualizable
{


    public function visualize(int $depth = 100)
    {
        $this->visAddMyNode();
        return $this;
    }


    public function visGetJSON(int $depth = 100)
    {
        return json_encode($this->visGetArray($depth, true), JSON_PRETTY_PRINT);
    }


    protected function visGetArray(int $depth = 100, bool $reindex_nodes = false): array
    {
        $vis = &$this->visGetStore();

        if (!count($vis['nodes'])) {
            $this->visualize($depth);
        }

        $this->visAddRootNodes()
            ->visRemoveDuplicateNodes()
            ->visRemoveDuplicateEdges()
            ->visAddMissingNodes();

        sort($vis['edges']);
        return [
            'nodes' => $reindex_nodes ? array_values($vis['nodes']) : $vis['nodes'],
            'edges' => $vis['edges'],
        ];
    }


    protected function &visGetStore()
    {
        $store = &Store::getStatic('vis', ['edges' => [], 'nodes' => []]);
        return $store;
    }


    protected function visNodeExists($node): bool
    {
        $vis = &$this->visGetStore();
        return isset($vis['nodes'][is_object($node) ? $node->oid() : $node]);
    }


    protected function visAddNode($node, array $properties = [])
    {
        $oid = is_object($node) ? $node->oid() : $node;

        if ($this->visNodeExists($oid)) {
            return $this;
        }

        $vis = &$this->visGetStore();
        $vis['nodes'][$oid] =
            array_replace([
                'id' => $oid,
                'label' => is_object($node) ? $node->getShortClass() : $node,
                'title' => $oid,
            ], $properties
        );
        return $this;
    }


    protected function visAddEdge($from, $to, array $properties = [])
    {
        $from_oid = is_object($from) ? $from->oid() : $from;
        $to_oid = is_object($to) ? $to->oid() : $to;
        $from_class = is_object($from) ? $from->getShortClass() : $from;
        $to_class = is_object($to) ? $to->getShortClass() : $to;

        $vis = &$this->visGetStore();
        $vis['edges'][] =
            array_replace([
                'from' => $from_oid,
                'to' => $to_oid,
                'title' => $from_class.' has a relationship with '.$to_class,
            ], $properties
        );
        return $this;
    }


    protected function visAddRootNodes()
    {
        $vis = &$this->visGetStore();
        foreach (Indicator::ROOT_INPUT as $root) {
            $vis['nodes'][$root] = [
                'id' => $root,
                'label' => $root,
                'title' => $root.' source',
                'group' => 'root_input',
            ];
        }
        return $this;
    }


    protected function visAddMyNode()
    {
        return $this->visAddNode($this);

    }

    protected function visRemoveDuplicateNodes()
    {
        $vis = &$this->visGetStore();
        $uniq = [];
        foreach ($vis['nodes'] as $node) {
            foreach ($uniq as $copy) {
                if ($node['id'] === $copy['id']) {
                    continue 2;
                }
            }
            $uniq[] = $node;
        }
        $vis['nodes'] = $uniq;
        return $this;
    }


    protected function visRemoveDuplicateEdges()
    {
        $vis = &$this->visGetStore();
        $uniq = [];
        foreach ($vis['edges'] as $edge) {
            foreach ($uniq as $copy) {
                if (json_encode($edge) === json_encode($copy)) {
                    continue 2;
                }
            }
            $uniq[] = $edge;
        }
        $vis['edges'] = $uniq;
        return $this;
    }


    protected function visAddMissingNodes()
    {
        $vis = &$this->visGetStore();
        $nodes = array_keys($vis['nodes']);
        foreach ($vis['edges'] as $edge) {
            foreach (['from', 'to'] as $end) {
                if (!in_array($oid = $edge[$end], $nodes)) {
                    $vis['nodes'][$oid] = [
                        'id' => $oid,
                        'label' => $oid
                    ];
                    //dump('add missing '.$oid);
                }
            }
        }
        return $this;
    }


    public function visMerge(array ...$subjects)
    {
        $vis = &$this->visGetStore();
        foreach ($subjects as $subject) {
            $vis['nodes'] = array_replace($vis['nodes'], $subject['nodes']);
            $vis['edges'] = array_replace($vis['edges'], $subject['edges']);
        }
        return $this;
    }


    public function visReset()
    {
        $vis = &$this->visGetStore();
        $vis = null;
        return $this;
    }
}
