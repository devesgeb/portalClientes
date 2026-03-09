<?php

use Config\Assets;

// Verificar si la función no existe para evitar redeclaración
if (!function_exists('asset_url')) {
    /**
     * Obtiene la URL completa de un asset
     * 
     * @param string $file Nombre del archivo (ej: 'bootstrap.min.css')
     * @param string $type Tipo de asset: 'css', 'js', 'images', 'fonts'
     * @return string URL completa
     */
    function asset_url(string $file = '', string $type = 'css'): string
    {
        // Obtener configuración
        $config = config('Assets');
        
        // Si no se encuentra la configuración, usar valores por defecto
        if (!$config) {
            // Valores por defecto si no hay configuración
            $base_url = rtrim(base_url(), '/');
            $folders = [
                'css' => 'assets/css/',
                'js' => 'assets/js/',
                'images' => 'assets/images/',
                'fonts' => 'assets/fonts/'
            ];
            
            $folder = $folders[$type] ?? 'assets/';
            
            // Agregar extensión si no la tiene
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (empty($extension)) {
                if ($type === 'css') $file .= '.css';
                if ($type === 'js') $file .= '.js';
            }
            
            return $base_url . '/' . $folder . $file;
        }
        
        // Usar la configuración
        return $config->url($file, $type);
    }
}

if (!function_exists('css')) {
    /**
     * Genera etiquetas <link> para CSS
     * 
     * @param mixed $files String o array con nombres de archivos
     * @return string HTML con etiquetas <link>
     */
    function css($files): string
    {
        if (empty($files)) {
            return '';
        }
        
        // Convertir a array si es string
        $files = is_array($files) ? $files : [$files];
        
        $html = '';
        foreach ($files as $file) {
            if (!empty($file)) {
                $url = asset_url($file, 'css');
                $html .= '<link rel="stylesheet" href="' . esc($url) . '">' . PHP_EOL;
            }
        }
        
        return $html;
    }
}

if (!function_exists('js')) {
    /**
     * Genera etiquetas <script> para JavaScript
     * 
     * @param mixed $files String o array con nombres de archivos
     * @param string $position 'head' o 'footer'
     * @return string HTML con etiquetas <script>
     */
    function js($files, string $position = 'footer'): string
    {
        if (empty($files)) {
            return '';
        }
        
        // Convertir a array si es string
        $files = is_array($files) ? $files : [$files];
        
        $html = '';
        foreach ($files as $file) {
            if (!empty($file)) {
                $url = asset_url($file, 'js');
                $defer = ($position === 'head') ? ' defer' : '';
                $html .= '<script src="' . esc($url) . '"' . $defer . '></script>' . PHP_EOL;
            }
        }
        
        return $html;
    }
}

if (!function_exists('img')) {
    /**
     * Genera etiqueta <img> o devuelve URL de imagen
     * 
     * @param string $file Nombre del archivo de imagen
     * @param string $alt Texto alternativo
     * @param array $attributes Atributos adicionales
     * @return string HTML o URL
     */
    function img(string $file, string $alt = '', array $attributes = []): string
    {
        $url = asset_url($file, 'images');
        
        // Si no hay atributos adicionales, devolver solo la URL
        if (empty($alt) && empty($attributes)) {
            return $url;
        }
        
        // Construir etiqueta img
        $attr = '';
        if (!empty($alt)) {
            $attributes['alt'] = $alt;
        }
        
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . esc($value) . '"';
        }
        
        return '<img src="' . esc($url) . '"' . $attr . '>';
    }
}

if (!function_exists('load_assets')) {
    /**
     * Carga un conjunto de assets
     * 
     * @param string $group Nombre del grupo (opcional)
     * @return array Array con 'css' y 'js'
     */
    function load_assets(string $group = 'default'): array
    {
        $config = config('Assets');
        
        $cssFiles = $config->defaultCSS ?? [];
        $jsFiles = $config->defaultJS ?? [];
        
        return [
            'css' => css($cssFiles),
            'js' => js($jsFiles)
        ];
    }
}