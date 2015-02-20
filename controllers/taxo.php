<?php

class Taxo extends Controller {

    public function index(Request $request, Database $db)
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

        $taxons = $db->select('taxo', 't')
                ->inner_join('autor', 'a')->on('autor_id')
                ->inner_join('clasificacio', 'c')->on('clasificacio_id')
                ->fields('t.nom', 't.taxo_id', 't.observacions',
                         'c.nom as clasificacio_nom', 'c.clasificacio_id',
                         'a.nom as autor_nom', 'a.autor_id')
                ->where_params($params)
                ->fetch();


        return response('ok')->json([
            'items_per_page'=> $items_page,
            'items'=> count($taxons),
            'result'=> array_slice($taxons, $items_page * ($pagina - 1), $items_page)
        ]);

    }

    public function get($taxo_id, Database $db)
    {
        $taxo = $db->select('taxo', 't')
              ->inner_join('autor', 'a')->on('autor_id')
              ->inner_join('clasificacio', 'c')->on('clasificacio_id')
              ->fields('t.nom', 't.taxo_id', 't.observacions',
                       'c.nom as clasificacio_nom', 'c.clasificacio_id',
                       'a.nom as autor_nom', 'a.autor_id')
              ->where('t.taxo_id', '=', $taxo_id)
              ->fetch()[0];

        $taxo['images'] = [];

        $name = urlencode($taxo['nom']);
        $search = file_get_contents("http://eol.org/api/search/1.0.json?q=$name&page=1&exact=true");
        $search = json_decode($search);

	$relative_dir = "/taxons/{$taxo_id}";

        $dir = DATA_DIR . $relative_dir;
        $url = DATA_URL . $relative_dir;

        if(!is_dir($dir)) mkdir($dir, 0755, true);

        if($search->totalResults > 0) {

            $result = $search->results[0];
            $object = file_get_contents("http://eol.org/api/pages/1.0/{$result->id}.json?images=5&videos=0&sounds=0&maps=0&text=0&iucn=true&subjects=&licenses=all&details=true&common_names=false&synonyms=false&references=false&vetted=0&cache_ttl=");
            $object = json_decode($object);

            $images = $object->dataObjects;
            $count = 0;

            foreach($images as $image_object) {

		$taxo_image = $db->select('taxo_imatge', 'ti')
				 ->fields('ti.*')
				 ->where('ti.eol_id', '=', $image_object->identifier)
				 ->where('ti.taxo_id', '=', $taxo['taxo_id'])
				 ->fetch();

		if(count($taxo_image) == 0) {

		    $db->insert('taxo_imatge')->values([
			'taxo_id' => $taxo['taxo_id'],
			'llicencia' => $image_object->license,
			'font' => $image_object->mediaURL,
			'eol_id' => $image_object->identifier
		    ])->execute();

		    $image_id = $db->last_id();

                    $image = new Image($image_object->mediaURL);
                    $image->resize_height(300);
                    $image->save("{$dir}/{$image_id}.jpg", IMAGETYPE_JPEG);

		}

            }

        }

	$imatges = $db->select('taxo_imatge', 'ti')
		      ->fields('ti.taxo_imatge_id')
		      ->where('ti.taxo_id', '=', $taxo['taxo_id'])
		      ->fetch(PDO::FETCH_COLUMN);

	array_walk($imatges, function(&$image_id, $key, $taxo_id) {
	    $image_id = ROOT_URL . "/taxo/{$taxo_id}/image/{$image_id}";
	}, $taxo['taxo_id']);

	$taxo['images'] = $imatges;

        return response('ok')->json($taxo);

    }

    public function image($taxo_id, $image_id, Database $db) {

	$taxo_image = $db->select('taxo_imatge', 'ti')
			 ->fields('ti.*')
			 ->where('ti.taxo_imatge_id', '=', $image_id)
			 ->where('ti.taxo_id', '=', $taxo_id)
			 ->fetch();

        $image_file = DATA_DIR . "/taxons/{$taxo_id}/{$image_id}.jpg";

	if(file_exists($image_file) && count($taxo_image) > 0) {

	    return response('ok')->image($image_file);

	} else {

            return response('not_found')->json([]);

	}

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

    public function delete($taxo_id, Request $request, Database $db)
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
