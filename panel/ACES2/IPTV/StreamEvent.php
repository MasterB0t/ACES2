<?php

namespace ACES2\IPTV;

class StreamEvent {

    private $EVENTS = [];

    public $id = 0;
    public $type = '';
    public $active = true;
    public $hide_streams_on_no_event = false;
    public $providers  = [];
    private $preStream = false;
    public $stream_image = '';
    public $stream_font = '';
    public $stream_font_color = '#FFFFFF';
    public $stream_font_size = 52;

    public function __construct(int $id = null) {

        $this->setEvents();
        if(is_null($id))
            return;

        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM iptv_dynamic_events WHERE id=$id");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("Event #$id not found.");

        $this->id = $id;
        $this->type = $row['type'];
        $this->active = (bool)$row['active'] == 1;
        $this->hide_streams_on_no_event = (bool)$row['hide_streams_on_no_event'] == 1;
        $this->preStream = (bool)$row['pre_stream'] == 1;
        $this->providers = json_decode($row['providers'], 1);

        $options = json_decode($row['options'], 1);
        $this->stream_image = $options['stream_image'];
        $this->stream_font = $options['stream_font'];
        $this->stream_font_color = $options['stream_font_color'];
        $this->stream_font_size = $options['stream_font_size'];

    }

    public function getPreStream() : bool {
        return $this->preStream;
    }

    public function setPreStream(bool $pre_stream) {
        if(!$pre_stream) {
            $this->preStream = false;
            return;
        }

        if(!$this->stream_font)
            throw new \Exception("Font is queried required.");
        if(!$this->stream_image)
            throw new \Exception("Stream image is required.");
        if(!$this->stream_font_color)
            throw new \Exception("Stream font color is required.");

        $this->preStream = true;
    }

    public function setActive(bool $active) {
        $this->active = $active;
    }
    public function setHideStreamsOnNoEvent(bool $hide_streams_on_no_event) {
        $this->hide_streams_on_no_event = $hide_streams_on_no_event;
    }
    public function setProviders(array $providers) {
        $this->providers = [];
        foreach($providers as $provider) {
            if((int)$provider)
                $this->providers[] = (int)$provider;
        }
    }
    public function setStreamFont(string $stream_font) {
        $this->stream_font = $stream_font;
    }
    public function setStreamFontColor(string $stream_font_color) {
        if(!preg_match('/^#?([0-9a-fA-F]{6}){1,2}$/', $stream_font_color))
            throw new \Exception("Stream font color is invalid.");
        $this->stream_font_color = strtoupper($stream_font_color);
    }
    public function setStreamFontSize(int $stream_font_size) {
        $this->stream_font_size = $stream_font_size;
    }
    public function setStreamImage(string $stream_image) {
        $this->stream_image = $stream_image;
    }

    public function save() {

        $db = new \ACES2\DB;
        $json = json_encode($this->providers);

        $options = json_encode(array(
            'stream_font' => $this->stream_font,
            'stream_font_size' => $this->stream_font_size,
            'stream_image' => $this->stream_image,
            'stream_font_color' => $this->stream_font_color,
        ));

        $db->query("UPDATE iptv_dynamic_events SET active='$this->active', 
                               hide_streams_on_no_event = '$this->hide_streams_on_no_event',  providers='$json',
                               options = '$options', pre_stream = $this->preStream
           WHERE id=$this->id");

    }

    static public function add(string $event_type, array $providers, bool $active = true , bool $hide_streams_on_no_event = false ) {

        $Event = new self();

        if(!in_array($event_type, $Event->EVENTS))
            throw new \Exception("Unknown event type '$event_type'.");

        $Event->setProviders($providers);
        $json = json_encode($Event->providers);

        $db = new \ACES2\DB;
        $db->query("INSERT INTO iptv_dynamic_events ( type, active, hide_streams_on_no_event, providers) 
            VALUES ('$event_type', '$active', '$hide_streams_on_no_event', '$json')
        ");

        return new self($db->insert_id);

    }

    private function setEvents() {
        $this->EVENTS = array('MLB', 'NFL', 'NBA');
    }

}