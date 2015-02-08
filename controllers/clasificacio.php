<?php 

class Clasificacio extends Controller {


   public function __consturctor($db)
   {
      parent::__constructor($db);
   }

   public function get_clasificacions($params, $pagina = 1)
   {
			$items_page = 30;

			$param_type = [
				'nom' => 'like'
			];

	   $res = new Query($this->db);
	   $clasificacions = $res->select('clasificacio', [
		   'clasificacio' => ['*'],
		   'clasificacio_tipus' => ['clasificacio_tipus_nom' => 'nom'],
		   ])
	   ->inner_join('clasificacio_tipus', 'clasificacio_tipus_id', 'clasificacio', 'tipus')
	   ->where($params, $param_type)
       ->order_by('clasificacio.nom', 'ASC')
	   ->execute();

       return [
           'sql' => $res->sql,
           'status' => 'ok',
		   'items' => intval(count($clasificacions)),
		   'tems_per_page' => intval($items_page),
           'result' => array_slice($clasificacions, $items_page * ($pagina - 1), $items_page)
       ];

   }

};

?>
