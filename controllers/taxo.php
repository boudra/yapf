<?php

class Taxo extends Controller {

    public function index(Request $request, Database $db)
    {
        $items_page = $request->get('items_per_page', 30);
        $pagina = $request->get('page', 1);

        $param_type = [
            'nom' => 'like'
        ];

        $params = $request->only('nom', 'taxo_id', 'autor_id', 'clasificacio_id');

        $taxons = $db->query()->select('taxo', [
            'taxo' => ['nom', 'taxo_id', 'observacions'],
            'clasificacio' => ['clasificacio_nom' => 'nom', 'clasificacio_id'],
            'autor' => ['autor_nom' => 'nom', 'autor_id']
        ]
        )->inner_join('clasificacio', 'clasificacio_id')
                ->inner_join('autor', 'autor_id')
                ->where($params, $param_type)
                ->order_by('taxo.nom', 'ASC')
                ->execute();

        return response('ok')->json([
            'sql'=> $res->sql,
            'result'=> array_slice($taxons, $items_page * ($pagina - 1), $items_page),
            'items'=> count($taxons),
            'items_per_page'=> $items_page
        ]);

    }

    public function get($id)
    {

        $sql = 'SELECT t.nom, t.taxo_id, t.observacions, t.grup,
			c.clasificacio_id, c.nom AS clasificacio_nom,
			a.autor_id, a.nom AS autor_nom
			FROM taxo t
			INNER JOIN clasificacio c ON
			c.clasificacio_id = t.clasificacio_id
			INNER JOIN autor a ON
			a.autor_id = t.autor_id
			WHERE t.taxo_id = :id;';

        $query = $this->db->prepare($sql);
        $query->execute(array('id' => $id));

        return response('ok')->json([
            'result' => $query->fetch(PDO::FETCH_ASSOC)
        ]);

    }

    public function update($params)
    {
        $res = new Query($this->db);
        $res->update('taxo', [
			'taxo' => ['nom', 'observacions', 'clasificacio_id', 'autor_id', 'grup']
        ], $params)
			->where(['taxo_id' => $params['taxo_id']])
			->execute();

        return [
			'sql' => $res->sql,
			'status' => 'ok',
			'result' => $params
        ];
    }

    public function create($params)
    {
        $sql = 'INSERT INTO taxo VALUES(NULL, :nom, :observacions, :clasificacio_id, :autor_id, 1, 0);';

        if(isset($params['observacions'])) {
            $params['observacions'] = '';
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);

        $id = $this->db->lastInsertId();
        $res = $this->put_taxo([
            'taxo_id' => $id,
            'grup' => $id
        ]);

        return $res;
    }

    public function delete($id, Request $request, Database $db)
    {
        var_dump($request->get_all());

        $query = $db->select('taxo','tx')
               ->inner_join('autor', 'at')
               ->on('autor_id')
               ->maybe('taxo_nom', 'autor_nom')
               ->fields('tx.taxo_id as id', 'at.autor_nom')
               ->where_params($request->get_all())
               ->build();

        $res = $db->table('taxo')
             ->fields('taxo_id', 'autor.nom')
             ->inner_join('autor')
             ->on('autor_id')
             ->where('taxo_id', '<',  25)
             ->fetch();

        return response('ok')->json($res);
    }

};

?>
