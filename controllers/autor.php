<?php

class Autor extends Controller {

    public function get_autors($params, $pagina = 0)
    {
        $items_page = 30;

        $param_type = [ 'nom' => 'like' ];

        $res = new Query($this->db);
        $autors = $res ->select('autor', [
                    'autor' => ['autor_id', 'nom', 'nom_hist']
                ]
            )
            ->where($params, $param_type)
            ->execute();

        $this->response['sql'] = $res->sql;
        $this->response['result'] = $pagina > 0
            ? array_slice($autors, $items_page * ($pagina - 1), $items_page) :
                $autors;

        $this->response['items'] = intval(count($autors));
        $this->response['tems_per_page'] = intval($items_page);
        $this->response['status'] = 'ok';
    }

    public function get_autor($params, $id) 
    {
        $sql = 'SELECT *
            FROM autor a
            WHERE a.autor_id = :id;';

        $query = $this->db->prepare($sql);
        $query->execute(array('id' => $id));
        $this->response['result'] = $query->fetch(PDO::FETCH_ASSOC);
        $this->response['status'] = 'ok';
    }

    public function put_autor($params)
    {
        $sql = 'UPDATE taxo_sinonim ts
            SET ts.nom = :nom
            WHERE ts.per_defecte = 1
            AND ts.taxo_id = :taxo;';

        $query = $this->db->prepare($sql);
        $query->execute(array(
            'taxo' => $id,
            'nom' => $this->request->nom)
        );

        $sql = 'UPDATE taxo
            SET autor_id = :autor,
            clasificacio_id = :clasificacio,
            observacions = :observacions
            WHERE taxo_id = :taxo;';

        $query = $this->db->prepare($sql);
        $query->execute(array(
            'taxo' => $id,
            'autor' => $this->request->autor_id,
            'clasificacio' => $this->request->clasificacio_id,
            'observacions' => $this->request->observacions
        ));

        $this->response['status'] = 'ok';
    }

    public function post_autor()
    {
        $sql = 'INSERT INTO taxo VALUES(NULL, :observacions, :clasificacio, :autor);';

        $query = $this->db->prepare($sql);
        $query->execute(array(
            'autor' => $this->request->autor_id,
            'clasificacio' => $this->request->clasificacio_id,
            'observacions' => $this->request->observacions
        ));

        $id = $this->db->lastInsertId();
        $sql = 'INSERT INTO taxo_sinonim VALUES(NULL, :nom, 1, :taxo);';

        $query = $this->db->prepare($sql);
        $query->execute(array(
            'nom' => $this->request->nom,
            'taxo' => $id
        ));

        $this->response['status'] = 'ok';
    }

    public function delete_autor($params)
    {
        /* Actualitzem els taxons que referencien aquest autor */
        $sql = 'UPDATE taxo SET autor_id = 0 WHERE autor_id = :id;';
        $query = $this->db->prepare($sql);
        $query->execute(array('id' => $id));

        $sql = 'DELETE FROM autor WHERE autor = :id;';
        $query = $this->db->prepare($sql);
        $query->execute(array('id' => $id));

        $this->response['status'] = 'ok';
    }

};

?>
