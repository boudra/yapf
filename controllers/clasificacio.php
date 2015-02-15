<?php 

class Clasificacio extends Controller {


    public function index(Request $rq, Database $db)
    {
        $items_page = $rq->get('items_per_page', 30);
        $pagina = $rq->get('page', 1);

        $params = $rq->only('nom', 'clasificacio_id');

        $params = rename_keys($params, [
            'nom' => 'c.nom',
            'clasificacio_id' => 'c.clasificacio_id'
        ]);

        $query = $db->select('clasificacio', 'c')
               ->inner_join('clasificacio_tipus', 'ct')
               ->on('clasificacio_tipus_id')
               ->fields('c.*', 'ct.nom as clasificacio_tipus_nom')
               ->where_params($params)
               ->order_by('c.nom', 'ASC');

        $clasificacions = $query->fetch();

        return response('ok')->json([
            'sql'=> $query->sql,
            'items_per_page'=> $items_page,
            'items'=> count($clasificacions),
            'result'=> array_slice($clasificacions, $items_page * ($pagina - 1), $items_page)
        ]);

    }

};

?>
