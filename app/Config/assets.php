<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Assets extends BaseConfig
{
    /**
     * ============================================
     * CONFIGURACIÓN BÁSICA
     * ============================================
     */
    
    // Ruta base de assets desde la raíz del sitio
    // Ejemplo: si tus assets están en public/assets/
    public $basePath = 'public/assets/';
    
    // Subdirectorios
    public $directories = [
        'css'     => 'css/',
        'js'      => 'js/',
        'images'  => 'images/',
        'fonts'   => 'fonts/',
    ];
    
    // Versión para cache busting
    public $version = '1.0.0';
    
    /**
     * ============================================
     * ARCHIVOS POR DEFECTO
     * ============================================
     */
    public $defaultCSS = [
        'bootstrap.min.css',
        'style.css'
    ];
    
    public $defaultJS = [
        'jquery.min.js',
        'bootstrap.bundle.min.js'
    ];
    
    /**
     * ============================================
     * MÉTODOS DE UTILIDAD
     * ============================================
     */
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Asegurar que la ruta base termina con /
        $this->basePath = rtrim($this->basePath, '/') . '/';
        
        foreach ($this->directories as $key => $dir) {
            $this->directories[$key] = rtrim($dir, '/') . '/';
        }
    }
    
    /**
     * Obtiene la ruta completa para un tipo de asset
     */
    public function getPath(string $type = 'css'): string
    {
        $dir = $this->directories[$type] ?? $type . '/';
        return $this->basePath . $dir;
    }
    
    /**
     * Obtiene la URL completa para un archivo
     */
    public function url(string $file, string $type = 'css'): string
    {
        // Si ya es una URL completa, devolverla
        if (strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0) {
            return $file;
        }
        
        // Si no tiene extensión, agregarla
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (empty($extension)) {
            if ($type === 'css') {
                $file .= '.css';
            } elseif ($type === 'js') {
                $file .= '.js';
            }
        }
        
        // Construir la URL
        $path = $this->getPath($type) . $file;
        
        // Agregar base_url()
        $url = base_url($path);
        
        // Agregar versión para cache
        if (!empty($this->version)) {
            $url .= '?v=' . $this->version;
        }
        
        return $url;
    }
    
    /**
     * Obtiene URLs para múltiples archivos
     */
    public function urls(array $files, string $type = 'css'): array
    {
        $urls = [];
        foreach ($files as $file) {
            $urls[] = $this->url($file, $type);
        }
        return $urls;
    }
}