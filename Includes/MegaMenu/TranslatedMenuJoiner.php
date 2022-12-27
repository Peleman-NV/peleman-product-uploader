<?php

declare(strict_types=1);

namespace PelemanProductUploader\Includes\MegaMenu;

use wpdb;

class TranslatedMenuJoiner
{

    private wpdb $db;
    private string $defaultLang;

    public function __construct(wpdb $db, string $defaultLanguage = 'en')
    {
        $this->db = $db;
        $this->defaultLang = $defaultLanguage;
    }

    public function joinCreatedMenus($menus): void
    {
        $defaultLanguageMenu = [];
        $relationships = $this->get_menu_term_relationships(
            $menus,
            $defaultLanguageMenu
        );

        error_log("default language menu: " . print_r($defaultLanguageMenu));
        $this->update_menu_item_languages($relationships);
        $trid = $this->get_default_menu_trid($defaultLanguageMenu);
        $this->update_menu_containers($menus, $trid);
    }

    //consider putting this method into its own dedicated class
    public function joinTranslatedMenuWithDefaultMenu(int $createdMenuId, string $menuLanguage, int $parentMenuId): void
    {
        $trid = $this->get_menu_trid($parentMenuId);
        error_log("trid: {$trid}");
        $updateQuery = $this->db->prepare(
            "UPDATE {$this->db->prefix}icl_translations 
            SET trid = %d, language_code = %s, source_language_code = %s 
            WHERE (element_id = %d AND element_type = %s)",
            $trid,
            $menuLanguage,
            $this->defaultLang,
            $createdMenuId,
            'tax_nav_menu'
        );
        error_log($updateQuery);
        $this->db->get_results($updateQuery);
    }

    private function get_menu_trid(int $menuId): int
    {
        $sql = $this->db->prepare(
            "SELECT trid FROM {$this->db->prefix}icl_translations WHERE (element_id = %d AND element_type = %s)",
            $menuId,
            'tax_nav_menu',
        );
        $results = $this->db->get_results($sql);

        if (empty($results)) {
            throw new \Exception("Cannot join menu translation to original; original not found in database.", 400);
        }
        return (int)$results[0]->trid;
    }

    private function get_menu_term_relationships(array $menus, array &$defaultMenu): array
    {
        $relationships = [];
        foreach ($menus as $menu) {
            $lang = $menu['lang'];
            if ($lang === 'en') {
                $defaultMenu = $menu;
                continue;
            }
            // get existing terms for secondary languages
            $relationships[$lang] = $this->db->get_results("SELECT object_id FROM {$this->db->prefix}term_relationships WHERE term_taxonomy_id = {$menu['id']};");
        }

        return $relationships;
    }

    /**
     * update language codes of the menu items
     *
     * @param array $relations
     * @return void
     */
    private function update_menu_item_languages(array $relations): void
    {
        foreach ($relations as $language => $relationships) {
            $relations = implode(',', $relationships);
            $sql = $this->db->prepare(
                "UPDATE {$this->db->prefix}icl_translations SET language_code = %s, source_language_code = 'en' WHERE element_id IN (%s);",
                $language,
                $relations,
            );
            $this->db->get_results($sql);
        }
    }
/**
 * retrieve WMPL trid of the default menu
 *
 * @param array $defaultMenu
 * @return integer
 */
    private function get_default_menu_trid(array $defaultMenu): int
    {
        $tridSql = $this->db->prepare(
            "SELECT trid FROM {$this->db->prefix}icl_translations WHERE (language_code = 'en' AND element_id = %d);",
            (int)$defaultMenu['id']
        );
        $trid = (int)$this->db->get_results($tridSql)[0]->trid;
        error_log("default menu trid: {$trid}");
        return $trid;
    }

    /**
     * merge created menus with the default language menu
     *
     * @param array $menus
     * @param integer $defaultTrid
     * @return void
     */
    private function update_menu_containers(array $menus, int $defaultTrid): void
    {
        foreach ($menus as $menu) {
            if ($menu['lang'] === 'en') continue;
            $updateMenuContainersSql = $this->db->prepare(
                "UPDATE {$this->db->prefix}icl_translations SET language_code = %s, trid = %d, source_language_code = 'en' WHERE element_id = %d;",
                $menu['lang'],
                $defaultTrid,
                (int)$menu['id'],
            );
            $this->db->get_results($updateMenuContainersSql);
        }
    }
}
