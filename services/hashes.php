 <?php



     /**
     * Calcula el hash SHA-256 de los datos proporcionados.
     * @param string $data Los datos a hashear.
     * @return string El hash SHA-256.
     */

     function generateHash(string $data): string
    {
        return hash('sha256', $data);
    }

     /**
     * Obtiene el hash del último asiento registrado en la cadena.
     * @return string El hash anterior o 64 ceros si es el primer asiento (Génesis).
     */
   
  function getPreviousHash(): string
    {
        global $db;
        $stmt = $db->query("
            SELECT current_hash FROM vts_ledger 
            ORDER BY id DESC LIMIT 1
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si hay un registro, devuelve su hash. Si no, devuelve el hash de Génesis.
        return $result ? $result['current_hash'] : str_repeat('0', 64);
    }

?>
