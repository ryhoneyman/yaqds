function event_signal(e)
	local qglobals = eq.get_qglobals();
	local keeperName = "keeper";
	keeperName = keeperName .. eq.get_zone_guild_id();
	if(e.signal == 1) then -- azarack
		if(eq.get_entity_list():IsMobSpawnedByNpcTypeID(71111) == false and eq.get_entity_list():IsMobSpawnedByNpcTypeID(71031) == false) then
			eq.spawn2(71559,0,0,-602.2,-254.4,-333.5,201.5); -- NPC: Protector_of_Sky
		end
	end
	if(e.signal == 2) then
		if(qglobals[keeperName] == nil) then
			-- eq.set_timer("13",300000); what is this referred to?
			eq.set_global(keeperName,"1",3,"H1");
			eq.unique_spawn(71575,0,0,-1484,720,146,8.0); -- NPC: Keeper_of_Souls
			-- supposed to be 60-85 minute timer
			--eq.set_timer("soul",math.random(1920000) + 2880000);
		end
	end
end

function event_say(e)
	if(e.message:findi("hail")) then
		e.self:Say("Hello there, brave traveller. I sell keys that take you to other islands in this here Plane of Sky. My prices are the best around. Heh, heh.");
	end
end

function event_timer(e)
	if(e.timer == "soul") then
		eq.stop_timer("soul");
	end
end

-- END of FILE Zone:airplane  ID:2977 -- Key_Master