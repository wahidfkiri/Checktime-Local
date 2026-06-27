<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'raison_sociale',
        'sigle',
        'rccm',
        'ifu',
        'directeur',
        'email',
        'telephone',
        'adresse',
        'logo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'raison_sociale' => 'encrypted',
        'sigle' => 'encrypted',
        'rccm' => 'encrypted',
        'ifu' => 'encrypted',
        'directeur' => 'encrypted',
        'email' => 'encrypted',
        'telephone' => 'encrypted',
        'adresse' => 'encrypted',
    ];

    // =================== SCOPES ===================

    /**
     * Scope pour la recherche globale
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('raison_sociale', 'like', "%{$search}%")
              ->orWhere('sigle', 'like', "%{$search}%")
              ->orWhere('rccm', 'like', "%{$search}%")
              ->orWhere('ifu', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('directeur', 'like', "%{$search}%")
              ->orWhere('telephone', 'like', "%{$search}%")
              ->orWhere('adresse', 'like', "%{$search}%")
              ->orWhere('ville', 'like', "%{$search}%");
        });
    }

    // =================== ACCESSORS ===================

    /**
     * Nom complet avec sigle
     */
    public function getNomCompletAttribute()
    {
        $nom = $this->raison_sociale;
        if ($this->sigle) {
            $nom .= " ({$this->sigle})";
        }
        return $nom;
    }

    /**
     * RCCM formaté en majuscules
     */
    public function getRccmFormattedAttribute()
    {
        return strtoupper($this->rccm);
    }

    /**
     * Téléphone formaté
     */
    public function getTelephoneFormattedAttribute()
    {
        if (empty($this->telephone)) {
            return '-';
        }
        
        $phone = preg_replace('/[^0-9]/', '', $this->telephone);
        
        if (strlen($phone) === 8) {
            return substr($phone, 0, 2) . ' ' . substr($phone, 2, 2) . ' ' . 
                   substr($phone, 4, 2) . ' ' . substr($phone, 6, 2);
        }
        
        return $this->telephone;
    }

    /**
     * Email avec lien mailto
     */
    public function getEmailLinkedAttribute()
    {
        if (empty($this->email)) {
            return '-';
        }
        return '<a href="mailto:' . $this->email . '">' . $this->email . '</a>';
    }

    /**
     * Obtenir l'adresse complète
     */
    public function getAdresseCompleteAttribute()
    {
        $adresse = $this->adresse ?? '';
        if ($this->ville) {
            $adresse .= $adresse ? ', ' . $this->ville : $this->ville;
        }
        return $adresse ?: '-';
    }

    // =================== VALIDATION RULES ===================

    /**
     * Règles de validation pour la mise à jour
     */
    public static function updateRules($clientId = null)
    {
        return [
            'raison_sociale' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'rccm' => 'nullable|string|max:255',
            'ifu' => 'nullable|string|max:255',
            'directeur' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:500',
            'ville' => 'nullable|string|max:100',
        ];
    }
}