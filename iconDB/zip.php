<?php

/*
 * Zip file creation class
 * makes zip files on the fly...
 *
 * by Eric Mueller
 * http://www.themepark.com
 *
 * v1.1 9-20-01
 * v1.0 2-5-01
 * by Denis O.Philippov, webmaster@atlant.ru, http://www.atlant.ru
 *
 * PHP 8.x compatibility: replaced PHP4-style var declarations,
 * fixed undefined variables in add_dir().
 */

// official ZIP file format: http://www.pkware.com/appnote.txt

class zipfile
{
    private array $datasec    = [];   // array to store compressed data
    private array $ctrl_dir  = [];   // central directory
    private string $eof_ctrl_dir = "PK\x05\x06\0\0\0\0";  // end of Central directory record
    private int $old_offset  = 0;

    /**
     * Adds "directory" to archive.
     * Call this before putting any files in the directory.
     * $name - name of directory, e.g. "path/"
     */
    public function add_dir(string $name): void
    {
        $name = str_replace('\\', '/', $name);

        $fr  = "PK\x03\x04";
        $fr .= "\n\0";       // ver needed to extract
        $fr .= "\0\0";       // gen purpose bit flag
        $fr .= "\0\0";       // compression method
        $fr .= "\0\0\0\0";   // last mod time and date
        $fr .= pack('V', 0); // crc32
        $fr .= pack('V', 0); // compressed filesize
        $fr .= pack('V', 0); // uncompressed filesize
        $fr .= pack('v', strlen($name)); // length of pathname
        $fr .= pack('v', 0); // extra field length
        $fr .= $name;

        // "data descriptor" segment — all zero for directories
        $fr .= pack('V', 0); // crc32
        $fr .= pack('V', 0); // compressed filesize
        $fr .= pack('V', 0); // uncompressed filesize

        $this->datasec[] = $fr;

        $new_offset = strlen(implode('', $this->datasec));

        // central directory record
        $cdrec  = "PK\x01\x02";
        $cdrec .= "\0\0";       // version made by
        $cdrec .= "\n\0";       // version needed to extract
        $cdrec .= "\0\0";       // gen purpose bit flag
        $cdrec .= "\0\0";       // compression method
        $cdrec .= "\0\0\0\0";   // last mod time & date
        $cdrec .= pack('V', 0); // crc32
        $cdrec .= pack('V', 0); // compressed filesize
        $cdrec .= pack('V', 0); // uncompressed filesize
        $cdrec .= pack('v', strlen($name)); // length of filename
        $cdrec .= pack('v', 0); // extra field length
        $cdrec .= pack('v', 0); // file comment length
        $cdrec .= pack('v', 0); // disk number start
        $cdrec .= pack('v', 0); // internal file attributes
        $cdrec .= pack('V', 16); // external file attributes — 'directory' bit set
        $cdrec .= pack('V', $this->old_offset); // relative offset of local header
        $this->old_offset = $new_offset;
        $cdrec .= $name;

        $this->ctrl_dir[] = $cdrec;
    }

    /**
     * Adds "file" to archive.
     * $data - file contents
     * $name - name of file in archive (add path if needed)
     */
    public function add_file(string $data, string $name): void
    {
        $name = $this->fix_zipname(str_replace('\\', '/', $name));

        $fr  = "PK\x03\x04";
        $fr .= "\x14\0";     // ver needed to extract
        $fr .= "\0\0";       // gen purpose bit flag
        $fr .= "\x08\0";     // compression method
        $fr .= "\0\0\0\0";   // last mod time and date

        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len   = strlen($zdata);

        $fr .= pack('V', $crc);     // crc32
        $fr .= pack('V', $c_len);   // compressed filesize
        $fr .= pack('V', $unc_len); // uncompressed filesize
        $fr .= pack('v', strlen($name)); // length of filename
        $fr .= pack('v', 0);        // extra field length
        $fr .= $name;
        $fr .= $zdata;

        // "data descriptor"
        $fr .= pack('V', $crc);
        $fr .= pack('V', $c_len);
        $fr .= pack('V', $unc_len);

        $this->datasec[] = $fr;

        $new_offset = strlen(implode('', $this->datasec));

        // central directory record
        $cdrec  = "PK\x01\x02";
        $cdrec .= "\0\0";           // version made by
        $cdrec .= "\x14\0";         // version needed to extract
        $cdrec .= "\0\0";           // gen purpose bit flag
        $cdrec .= "\x08\0";         // compression method
        $cdrec .= "\0\0\0\0";       // last mod time & date
        $cdrec .= pack('V', $crc);
        $cdrec .= pack('V', $c_len);
        $cdrec .= pack('V', $unc_len);
        $cdrec .= pack('v', strlen($name)); // length of filename
        $cdrec .= pack('v', 0);     // extra field length
        $cdrec .= pack('v', 0);     // file comment length
        $cdrec .= pack('v', 0);     // disk number start
        $cdrec .= pack('v', 0);     // internal file attributes
        $cdrec .= pack('V', 32);    // external file attributes — 'archive' bit set
        $cdrec .= pack('V', $this->old_offset);
        $this->old_offset = $new_offset;
        $cdrec .= $name;

        $this->ctrl_dir[] = $cdrec;
    }

    /** Dumps the complete zip file as a string. */
    public function file(): string
    {
        $data    = implode('', $this->datasec);
        $ctrldir = implode('', $this->ctrl_dir);

        return $data
            . $ctrldir
            . $this->eof_ctrl_dir
            . pack('v', count($this->ctrl_dir))  // total # of entries "on this disk"
            . pack('v', count($this->ctrl_dir))  // total # of entries overall
            . pack('V', strlen($ctrldir))         // size of central dir
            . pack('V', strlen($data))            // offset to start of central dir
            . "\0\0";                             // .zip file comment length
    }

    private function fix_zipname(string $name): string
    {
        return strtr(
            $name,
            "\xa1\xa2\xa3\xa4\xa5\xa6\xa7\xa8\xa9\xaa\xab\xac\xad\xae\xaf\xb0\xb1\xb2\xb3\xb4\xb5\xb6\xb7\xb8\xb9\xba\xbb\xbc\xbd\xbe\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf7\xf8\xf9\xfa\xfb\xfc\xfd\xfe\xff",
            "\xad\xbd\x9c\xcf\xbe\xdd\xf5\xf9\xb8\xa6\xae\xaa\xf0\xa9\xee\xf8\xf1\xfd\xfc\xef\xe6\xf4\xfa\xf7\xfb\xa7\xaf\xac\xab\xf3\xa8\xb7\xb5\xb6\xc7\x8e\x8f\x92\x80\xd4\x90\xd2\xd3\xde\xd6\xd7\xd8\xd1\xa5\xe3\xe0\xe2\xe5\x99\x9e\x9d\xeb\xe9\xea\x9a\xed\xe8\xe1\x85 \x83\xc6\x84\x86\x91\x87\x8a\x82\x88\x89\x8d\xa1\x8c\x8b\xd0\xa4\x95\xa2\x93\xe4\x94\xf6\x9b\x97\xa3\x96\x81\xec\xe7\x98"
        );
    }
}
