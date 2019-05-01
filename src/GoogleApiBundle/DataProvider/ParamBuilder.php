<?php

namespace GoogleApiBundle\DataProvider;

class ParamBuilder
{
    protected $params = ['spaces' => 'drive', 'pageSize' => 1000];

    protected $fileFields = ['id', 'name', 'createdTime'];

    protected $query = '';

    public function getParams(): array
    {
        if ($this->query) {
            $this->params['q'] = $this->query;
        }

        $this->params['fields'] = sprintf('nextPageToken, files(%s)', implode(', ', $this->fileFields));

        return $this->params;
    }

    public function setLimit(int $limit): self
    {
        $this->params['pageSize'] = $limit;

        return $this;
    }

    public function setFolderId(string $folderId): self
    {
        $this->addQuery("'".$folderId."' in parents");

        return $this;
    }

    public function setFoldersOnly(): self
    {
        $this->addQuery("mimeType = 'application/vnd.google-apps.folder'");

        return $this;
    }

    public function setFilesOnly(): self
    {
        $this->addQuery("mimeType != 'application/vnd.google-apps.folder'");

        return $this;
    }

    public function setOrderBy($field, $order = 'asc'): self
    {
        if ($order !== 'asc' && $order !== 'desc') {
            return $this;
        }

        $this->params['orderBy'] = sprintf('%s %s', $field, $order);

        return $this;
    }

    public function addSelectFields(array $fields): self
    {
        foreach ($fields as $field) {
            if (!in_array($field, $this->fileFields, true)) {
                $this->fileFields[] = $field;
            }
        }

        return $this;
    }

    public function setSearchByName(string $name): self
    {
        $this->addQuery(sprintf("name contains '%s'", $name));

        return $this;
    }

    protected function addQuery(string $query): self
    {
        $this->query .= $this->query ? ' and ' : '';
        $this->query .= $query;

        return $this;
    }
}
