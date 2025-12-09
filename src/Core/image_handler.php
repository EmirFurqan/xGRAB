<?php

/**
 * Belirtilen fotoğrafın, web sunucusunun kök dizinine (/) göre yolunu döndürür.
 * Bu yol, PHP dosyanızın nerede (hangi klasörde) olduğundan bağımsız olarak, 
 * tarayıcının fotoğrafı bulmasını sağlar.
 *
 * @param string $fileName Fotoğrafın dosya adı (örneğin: "avatar.png").
 * @return string HTML <img> src özniteliği için uygun yol (örn: "/uploads/avatar.png").
 */
function getImagePath($fileName) {
    
    // Fotoğraflarınızın, web sitesinin kök dizinine göre nerede bulunduğunu belirtin.
    // Sizin durumunuzda, fotoğraf klasörü doğrudan kökteki 'uploads' klasörüdür.
    $imageDirectory = '/uploads/'; 
    
    // Yolu birleştirip döndürün.
    $imagePath = $imageDirectory . $fileName;
    
    return $imagePath;
}

?>