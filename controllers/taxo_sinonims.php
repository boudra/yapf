<?php

class TaxoSinonims extends Controller {

    public function index(Request $request, Database $db, $taxo_id)
    {

        $items_page = $request->get('items_per_page', 30);
        $pagina = $request->get('page', 1);

        $params = $request->only('nom', 'taxo_id', 'autor_id', 'clasificacio_id');

        $params = rename_keys($params, [
            'nom' => 't.nom',
            'taxo_id' => 't.taxo_id',
            'autor_id' => 't.autor_id',
            'clasificacio_id' => 't.clasificacio_id']
        );

        $query = $db->select('taxo', 't')
               ->inner_join('autor', 'a')->on('autor_id')
               ->inner_join('clasificacio', 'c')->on('clasificacio_id')
               ->fields('t.nom', 't.taxo_id', 't.observacions',
                        'c.nom as clasificacio_nom', 'c.clasificacio_id',
                        'a.nom as autor_nom', 'a.autor_id')
               ->where_params($params)
               ->where('t.taxo_id', '<>', '`t.grup`')
               ->where('t.grup', '=', $taxo_id);
        $taxons = $query->fetch();

        return response('ok')->json([
            'items_per_page'=> $items_page,
            'sql'=> $query->sql,
            'items'=> count($taxons),
            'result'=> array_slice($taxons, $items_page * ($pagina - 1), $items_page)
        ]);

    }

};

?>