function event_death_complete(e)
	local sirranName = "sirran";
	sirranName = sirranName .. eq.get_zone_guild_id();
	if(eq.get_entity_list():IsMobSpawnedByNpcTypeID(71058) == false) then
		eq.set_global(sirranName,"7",3,"M20");
		eq.unique_spawn(71058,0,0,-960,-1037,1093,64); -- NPC: Sirran_the_Lunatic
	end
end

-------------------------------------------------------------------------------------------------
-- Converted to .lua using MATLAB converter written by Stryd
-- Find/replace data for .pl --> .lua conversions provided by Speedz, Stryd, Sorvani and Robregen
-------------------------------------------------------------------------------------------------
