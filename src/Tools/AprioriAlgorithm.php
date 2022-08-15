<?php

namespace RennyPasardesa\Apriori\Tools;

use Illuminate\Support\Collection;
use RennyPasardesa\Apriori\Models\AssociationRule;

class AprioriAlgorithm
{
    public $min_support;
    public $min_confidence;
    public $itemset;
    public $itemset_count;
    public $initial_item;
    public $rules = [];
    public $k_itemset = [];
    public $item_set_count;
    public $association_candidate = [];
    public $model;
    
    // load data transaksi menggunakan parameter $itemset
    public function __construct($itemset)
    { 
        $this->min_support = config('pasardesa.min_support');
        $this->min_confidence = config('pasardesa.min_confidence');
        $this->itemset = $itemset;
        $this->itemset_count = count($itemset);

        $this->model = new AssociationRule;
    }

    // function menentukan nilai minimum support
    public function setMinSupport($min_support)
    {
        $this->min_support = $min_support;
    }

    // menentukan nilai minimum confidence
    public function setMinConfidence($min_confidence)
    {
        $this->min_confidence = $min_confidence;
    }

    //menghitung nilai kombinasi itemset 1,2,3
    private function support($k = 1)
    {
        //set data kombinasi itemset dalam array
        $count_filtered = 0;
        $k_itemset_filtered = [];

        //filter itemset
        $item_filtered = [];
        foreach ($this->k_itemset[$k] as &$k_itemset) {

            //rumus mencari nilai support
            $support = ($k_itemset['count'] / $this->itemset_count) * 100;

            //hasil nilai support
            $k_itemset['support'] = $support;

            //membandingkan hasil nilai support dengan nilai minimum support yang telah ditetapkan
            if ($support >= $this->min_support) {

                //filter hasil kombinasi itemset kedalam bentuk array
                $k_itemset_filtered[] = $k_itemset;

                // untuk menampung produk yang memenuhi syarat
                $item_filtered = array_merge($item_filtered, $k_itemset['items']);

                //
                $count_filtered += 1;
            }
        }

        // hasil dari itemset dibuat unique agar produk tidak berulang
        // [7,12,8,12] -> [7,12,8]
        $item_filtered = array_unique($item_filtered);

        // misal $count_filtered = 2
        // $k_itemset_filtered = [
        //     [
        //         'items' => [7,12],
        //         'count' => 4,
        //         'support' => 50,
        //     ],
        //     [
        //         'items' => [8,12],
        //         'count' => 4,
        //         'support' => 50,
        //     ],
        // ]
        // $item_filtered = [7,12,8]
        return [$count_filtered, $k_itemset_filtered, $item_filtered];
    }

    private function sampling($elements, $limit)
    {
        if ($limit == 1) {
            foreach ($elements as $element) {
                yield array($element);
            }
        }

        foreach ($elements as $i => $element) {
            $sub_perms = $this->sampling(array_merge(array_slice($elements, 0, $i), array_slice($elements, $i+1)), $limit-1);

            foreach ($sub_perms as $sub_perm) {
                yield array_merge(array($element), $sub_perm);
            }
        }
    }

    private function associationRules()
    {
        $candidates = $this->association_candidate;
        $count_candidates = count($candidates);

        $this->rules = [];
        $temp_rules = [];

        for ($i=2; $i <= $count_candidates; $i++) {
            $result = $this->sampling($candidates, $i);

            foreach ($result as $row) {
                $check_duplicate_count = $row;
                $check_duplicate = array_unique($row);
                if ($check_duplicate_count != $check_duplicate) {
                    continue;
                }

                $new_row = $row;

                $B = array_pop($row);
  
                sort($row);
                $rule_index = implode('_', $row);
                $count_row = count($row);

                if ($count_row == 1 || ! isset($temp_rules[$rule_index])) {
                    $temp_rules[$rule_index] = true;

                    $all_count = $this->findItemsetCount($new_row);
                    $count_items = $this->findItemsetCount($row);

                    if ($count_items > 0) {
                        $confidence = $all_count / $count_items * 100;
                    } else {
                        $confidence = 0;
                    }

                    $support = $this->k_itemset[1][$B]['support'];
                    $lift = $confidence/$support;


                    $this->rules[] = [
                        'items' => $row,
                        'recommendation' => $B,
                        'all_count' => $all_count,
                        'count_items' => $count_items,
                        'confidence' => $confidence,
                        'lift' => $lift,
                    ];
                }
            }
        }

        return $this->rules;
    }

    private function readItemset()
    {
        $this->initial_item = [];
        foreach ($this->itemset as $transaction_id => $items) {
            foreach ($items as $item) {
                if (! isset($this->initial_item[$item])) {
                    // Menyimpan k-itemset & menghitung jumlah transaksi yang mengandung items
                    $this->initial_item[$item]['items'][] = $item;
                    $this->initial_item[$item]['count'] = 0;
                }

                $this->initial_item[$item]['count'] += 1;
            }
        }

        $this->k_itemset[1] = $this->initial_item;
    }

    private function combination($combinations, $k) {
        $combinations = array_values($combinations);
        $count = count($combinations);
        $members = pow(2, $count);
        $return = array();

        for($i = 0; $i < $members; $i ++) {
            $b = sprintf("%0" . $count . "b", $i);
            $out = array();

            for($j = 0; $j < $count; $j ++) {
                if ($b[$j] == '1') {
                    $out[] = $combinations[$j];
                }
            }

            if (count($out) == $k) {
                $return[] = [
                    'items' => $out,
                    'count' => $this->findItemsetCount($out)
                ];
            }
        }

        return $return;
    }

    public function findItemsetCount($combinations)
    {
        $count = 0;
        foreach ($this->itemset as $transaction_id => $items) {
            $found = 1;
            foreach ($combinations as $combination) {
                if (! in_array($combination, $items)) {
                    $found = 0;
                }
            }

            $count += $found;
        }

        $this->item_set_count[implode(',', $combinations)] = $count;

        return $count;
    }

    public function process()
    {
        // Untuk menghitung jumlah transaksi tiap produk & 1-itemset
        $this->readItemset();

        // menghitung support dari itemset ke $k, dan membuat kombinasi itemset selanjutnya
        $k = 1;
        while (true) {
            [$count_filtered, $k_itemset_filtered, $item_filtered] = $this->support($k);

            if ($count_filtered == 0) {
                break;
            } else {
                $k += 1;

                if (count($item_filtered) < $k) {
                    break;
                }

                $this->k_itemset[$k] = $this->combination($item_filtered, $k);
            }
        }

        $last_k_itemset = end($this->k_itemset);

        $this->association_candidate = [];
        foreach ($last_k_itemset as $key => $k_item_set) {
            $this->association_candidate = array_merge($this->association_candidate, $k_item_set['items']);
        }

        // misal $this->association_candidate = [7,8,12]
        $this->association_candidate = array_unique($this->association_candidate);
        $this->associationRules();

        $this->save();
    }

    public function save()
    {
        $this->model->whereRaw('1 = 1')->delete();

        foreach ($this->rules as $rule) {
            $associationRule = $this->model->create([
                'products' => $rule['items'],
                'recommendation' => $rule['recommendation'],
                'confidence' => $rule['confidence'],
                'lift' => $rule['lift'],
            ]);
        }
    }
}
